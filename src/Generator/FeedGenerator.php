<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Event\FeedItemBuiltEvent;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Filter\FilterEvaluatorInterface;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Format\FormatInterface;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Lookup\LookupReferenceResolverInterface;
use Setono\SyliusFeedPlugin\Mapping\MappingResolverInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Setono\SyliusFeedPlugin\Validator\FeedItemValidatorInterface;
use Setono\SyliusFeedPlugin\Writer\CsvWriterConfig;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistryInterface;
use Setono\SyliusFeedPlugin\Writer\SplitManifestRegistryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Orchestrates the per-context generation pipeline (§6): resolve → transform → event → validate →
 * write. The item stream is handed to the {@see OutputWriterInterface}, which streams it to storage
 * and, when a size limit is reached, splits it into parts and optionally gzips each file (§12).
 */
final class FeedGenerator implements FeedGeneratorInterface
{
    public function __construct(
        private readonly FeedTypeRegistryInterface $feedTypeRegistry,
        private readonly MappingResolverInterface $mappingResolver,
        private readonly FormatRegistryInterface $formatRegistry,
        private readonly FeedWriterRegistryInterface $writerRegistry,
        private readonly FieldMappingEvaluatorInterface $fieldMappingEvaluator,
        private readonly FilterEvaluatorInterface $filterEvaluator,
        private readonly LookupReferenceResolverInterface $lookupReferenceResolver,
        private readonly FeedItemValidatorInterface $feedItemValidator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly OutputWriterInterface $outputWriter,
        private readonly SplitManifestRegistryInterface $splitManifestRegistry,
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

        $this->applyRequestContext($context);

        $exclusions = new ExclusionCollector();

        $output = $this->outputWriter->write(
            $this->items($feed, $context, $format, $exclusions),
            $writer,
            $config,
            $context,
            (string) $feed->getCode(),
            $context->key(),
            $format->getWriter(),
            $this->resolveSplitLimit($format, $feed),
            $this->resolveGzip($feed),
            $this->splitManifestRegistry->get($format->getSplitManifest()),
        );

        return new GenerationResult(
            $output->primaryPath,
            $output->itemCount,
            $exclusions->count(),
            $output->bytes,
            $exclusions->errors(),
            $output->paths,
        );
    }

    /**
     * The pipeline for one context: resolve each source's items, run them through binding, filters,
     * the build event, skip and validation, recording every exclusion and yielding the items that
     * survive to the writer.
     *
     * @return iterable<FeedItem>
     */
    private function items(FeedInterface $feed, FeedContext $context, FormatInterface $format, ExclusionCollector $exclusions): iterable
    {
        $requiredFields = $format->getRequiredFields();
        $itemValidationGroups = $format->getItemValidationGroups();

        foreach ($this->sortedSources($feed) as $source) {
            $feedType = $this->feedTypeRegistry->get((string) $source->getFeedType());
            $availableFields = $feedType->getAvailableFields();
            $mappings = $this->mappingResolver->resolve($feed, $source);
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

                yield $item;
            }
        }
    }

    /**
     * The effective split limit (§12): the format default with the feed's `formatConfig['split']`
     * overrides merged over it, so an admin can lower it (e.g. to force splitting for testing).
     *
     * @return array{maxItems?: int, maxBytes?: int}
     */
    private function resolveSplitLimit(FormatInterface $format, FeedInterface $feed): array
    {
        $limit = $format->getSplitLimit();

        $override = $feed->getFormatConfig()['split'] ?? [];
        if (is_array($override)) {
            foreach (['maxItems', 'maxBytes'] as $key) {
                $value = $override[$key] ?? null;
                if (is_numeric($value)) {
                    $limit[$key] = (int) $value;
                }
            }
        }

        return $limit;
    }

    private function resolveGzip(FeedInterface $feed): bool
    {
        return (bool) ($feed->getFormatConfig()['gzip'] ?? false);
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
     * The union of every source's output fields, in first-seen order — the CSV header.
     *
     * @return list<string>
     */
    private function unionHeader(FeedInterface $feed): array
    {
        $header = [];
        foreach ($this->sortedSources($feed) as $source) {
            foreach ($this->mappingResolver->resolve($feed, $source) as $mapping) {
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
}
