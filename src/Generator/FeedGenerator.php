<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Event\FeedItemBuiltEvent;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Filter\FilterEvaluatorInterface;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Lookup\LookupReferenceResolverInterface;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Setono\SyliusFeedPlugin\Validator\FeedItemValidatorInterface;
use Setono\SyliusFeedPlugin\Writer\CsvWriterConfig;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Orchestrates the per-context generation pipeline (§6): resolve → transform → event → validate →
 * write, streaming each item to storage. M1 is synchronous and single-chunk; partitioning,
 * fan-out and the publish gate land in later milestones.
 */
final class FeedGenerator implements FeedGeneratorInterface
{
    public function __construct(
        private readonly FeedTypeRegistryInterface $feedTypeRegistry,
        private readonly MappingPresetRegistryInterface $mappingPresetRegistry,
        private readonly FormatRegistryInterface $formatRegistry,
        private readonly FeedWriterRegistryInterface $writerRegistry,
        private readonly FieldMappingEvaluatorInterface $fieldMappingEvaluator,
        private readonly FilterEvaluatorInterface $filterEvaluator,
        private readonly LookupReferenceResolverInterface $lookupReferenceResolver,
        private readonly FeedItemValidatorInterface $feedItemValidator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FilesystemOperator $feedFilesystem,
    ) {
    }

    public function generate(FeedInterface $feed, FeedContext $context): GenerationResult
    {
        $format = $this->formatRegistry->get((string) $feed->getFormat());
        $writer = $this->writerRegistry->get($format->getWriter());

        $config = $format->getConfig()->withFeedMetadata($this->buildFeedMetadata($feed, $context));
        if ($config instanceof CsvWriterConfig) {
            // CSV needs a single header up front — the union of every source's output fields, so
            // heterogeneous multi-source rows line up under one header.
            $config = $config->withHeader($this->unionHeader($feed));
        }
        $requiredFields = $format->getRequiredFields();
        $itemValidationGroups = $format->getItemValidationGroups();

        $this->applyRequestContext($context);

        $stream = $this->openStream();
        $writer->open($stream, $context, $config);
        $writer->writePreamble();

        $itemCount = 0;
        $exclusions = new ExclusionCollector();

        foreach ($this->sortedSources($feed) as $source) {
            $feedType = $this->feedTypeRegistry->get((string) $source->getFeedType());
            $availableFields = $feedType->getAvailableFields();
            $mappings = $this->resolveMappings($feed, $source);
            $filterSet = new FilterSet($source->getFilters());

            foreach ($feedType->getDataSource()->getItems($context, $filterSet) as $entity) {
                $item = $feedType->createItem($entity, $context);

                // Bind the source resolver up front so pre-filters can resolve raw source fields;
                // mapping reuses the same (idempotent) binding. NOTE: all filters are evaluated per
                // item — pushing `pre` filters into the data source query is a future optimization.
                SourceResolverBinder::bind($item, $availableFields, $this->lookupReferenceResolver);

                $excludingPreFilter = $this->filterEvaluator->excludedBy($item, $filterSet->getPreFilters());
                if (null !== $excludingPreFilter) {
                    $exclusions->record($this->resolveItemIdentifier($item), sprintf('filter:pre:%s', (string) $excludingPreFilter->getField()));

                    continue;
                }

                $this->fieldMappingEvaluator->apply($item, $mappings, $availableFields);

                $excludingPostFilter = $this->filterEvaluator->excludedBy($item, $filterSet->getPostFilters());
                if (null !== $excludingPostFilter) {
                    $exclusions->record($this->resolveItemIdentifier($item), sprintf('filter:post:%s', (string) $excludingPostFilter->getField()));

                    continue;
                }

                $this->eventDispatcher->dispatch(new FeedItemBuiltEvent($item));

                if ($item->isSkipped()) {
                    $exclusions->record($this->resolveItemIdentifier($item), 'skipped');

                    continue;
                }

                $violations = $this->feedItemValidator->validate($item, $requiredFields, $itemValidationGroups);
                if ([] !== $violations) {
                    $exclusions->record($this->resolveItemIdentifier($item), 'validation:' . implode('; ', $violations));

                    continue;
                }

                $writer->writeItem($item);
                ++$itemCount;
            }
        }

        $writer->writeEpilogue();
        $writer->close();

        $path = sprintf('%s/%s.%s', (string) $feed->getCode(), $context->key(), $format->getWriter());

        rewind($stream);
        $this->feedFilesystem->writeStream($path, $stream);
        $stat = fstat($stream);
        $bytes = false === $stat ? 0 : $stat['size'];
        fclose($stream);

        return new GenerationResult($path, $itemCount, $exclusions->count(), $bytes, $exclusions->errors());
    }

    /**
     * The item's identity for exclusion reporting: its output id if the mapping has produced one,
     * else null (§11). A pre-filter exclusion happens before mapping, so the id is usually null there.
     */
    private function resolveItemIdentifier(FeedItem $item): ?string
    {
        foreach (['g:id', 'id'] as $key) {
            $value = $item->get($key);
            if (is_scalar($value) && '' !== (string) $value) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * The admin-editable FeedField rows are the source of truth once a source has any; the matching
     * MappingPreset is only the fallback for a source that was never seeded/edited (§10).
     *
     * @return list<FieldMapping>
     */
    private function resolveMappings(FeedInterface $feed, FeedSourceInterface $source): array
    {
        $fields = $source->getFields()->toArray();
        if ([] !== $fields) {
            usort(
                $fields,
                static fn (FeedFieldInterface $a, FeedFieldInterface $b): int => ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0),
            );

            return array_map(FieldMapping::fromFeedField(...), $fields);
        }

        foreach ($this->mappingPresetRegistry->forFeedType((string) $source->getFeedType()) as $preset) {
            if ($preset->getFormat() === $feed->getFormat()) {
                return $preset->getMapping();
            }
        }

        return [];
    }

    /**
     * The union of every source's output fields, in first-seen order — the CSV header.
     *
     * @return list<string>
     */
    private function unionHeader(FeedInterface $feed): array
    {
        $header = [];
        foreach ($this->sortedSources($feed) as $source) {
            foreach ($this->resolveMappings($feed, $source) as $mapping) {
                $outputField = $mapping->getOutputField();
                if (!in_array($outputField, $header, true)) {
                    $header[] = $outputField;
                }
            }
        }

        return $header;
    }

    /**
     * @return list<FeedSourceInterface>
     */
    private function sortedSources(FeedInterface $feed): array
    {
        $sources = array_values($feed->getSources()->toArray());
        usort(
            $sources,
            static fn (FeedSourceInterface $a, FeedSourceInterface $b): int => ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0),
        );

        return $sources;
    }

    /**
     * Feed-level document metadata for the writer's preamble (e.g. the RSS channel title/link/
     * description). Writers that have no preamble ignore it.
     *
     * @return array<string, string>
     */
    private function buildFeedMetadata(FeedInterface $feed, FeedContext $context): array
    {
        $title = (string) $feed->getCode();

        $link = '';
        $channel = $context->getChannel();
        if (null !== $channel && null !== $channel->getHostname()) {
            $link = 'https://' . $channel->getHostname();
        }

        return ['title' => $title, 'link' => $link, 'description' => $title];
    }

    private function applyRequestContext(FeedContext $context): void
    {
        $channel = $context->getChannel();
        if (null !== $channel && null !== $channel->getHostname()) {
            $this->urlGenerator->getContext()->setHost($channel->getHostname())->setScheme('https');
        }
    }

    /**
     * @return resource
     */
    private function openStream()
    {
        $stream = fopen('php://temp', 'w+b');

        // Defensive: an in-memory stream cannot be made to fail on demand, so this is uncoverable.
        // @codeCoverageIgnoreStart
        if (!is_resource($stream)) {
            throw new \RuntimeException('Could not open a temporary stream');
        }
        // @codeCoverageIgnoreEnd

        return $stream;
    }
}
