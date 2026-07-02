<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Event\FeedItemBuiltEvent;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidatorInterface;
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
        private readonly RequiredFieldsValidatorInterface $requiredFieldsValidator,
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

        $this->applyRequestContext($context);

        $stream = $this->openStream();
        $writer->open($stream, $context, $config);
        $writer->writePreamble();

        $itemCount = 0;
        $excludedCount = 0;

        foreach ($this->sortedSources($feed) as $source) {
            $feedType = $this->feedTypeRegistry->get((string) $source->getFeedType());
            $availableFields = $feedType->getAvailableFields();
            $mappings = $this->resolveMappings($feed, $source);

            foreach ($feedType->getDataSource()->getItems($context, new FilterSet($source->getFilters())) as $entity) {
                $item = new FeedItem($entity, $context);

                $this->fieldMappingEvaluator->apply($item, $mappings, $availableFields);

                $this->eventDispatcher->dispatch(new FeedItemBuiltEvent($item));

                if ($item->isSkipped() || [] !== $this->requiredFieldsValidator->findMissingFields($item, $requiredFields)) {
                    ++$excludedCount;

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
        fclose($stream);

        return new GenerationResult($path, $itemCount, $excludedCount);
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
