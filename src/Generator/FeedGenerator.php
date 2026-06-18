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
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\Mapping\SourceType;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Setono\SyliusFeedPlugin\Transformation\TransformationChain;
use Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidator;
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
        private readonly TransformationChain $transformationChain,
        private readonly RequiredFieldsValidator $requiredFieldsValidator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FilesystemOperator $feedFilesystem,
    ) {
    }

    public function generate(FeedInterface $feed, FeedContext $context): GenerationResult
    {
        $format = $this->formatRegistry->get((string) $feed->getFormat());
        $writer = $this->writerRegistry->get($format->getWriter());

        $config = array_merge($format->getConfig(), $feed->getFormatConfig(), ['preamble' => $this->buildPreamble($feed, $context)]);
        $requiredFields = $this->requiredFields($config);

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

                foreach ($mappings as $mapping) {
                    if (!$this->conditionSatisfied($mapping, $entity, $context, $availableFields)) {
                        continue;
                    }

                    $value = $this->transformationChain->apply(
                        $this->resolveValue($mapping, $entity, $context, $availableFields),
                        $mapping->getTransformations(),
                        $item,
                    );

                    if (null !== $value) {
                        $item->set($mapping->getOutputField(), $value);
                    }
                }

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
     * @param array<string, FieldDefinition> $availableFields
     */
    private function resolveValue(FieldMapping $mapping, object $entity, FeedContext $context, array $availableFields): mixed
    {
        return match ($mapping->getSourceType()) {
            SourceType::LITERAL => $mapping->getSourceValue(),
            SourceType::FIELD => isset($availableFields[$mapping->getSourceValue()])
                ? $availableFields[$mapping->getSourceValue()]->getResolver()->resolve($entity, $context)
                : null,
            // expression/twig source resolution lands in M4
            default => null,
        };
    }

    /**
     * @param array<string, FieldDefinition> $availableFields
     */
    private function conditionSatisfied(FieldMapping $mapping, object $entity, FeedContext $context, array $availableFields): bool
    {
        $condition = $mapping->getCondition();
        if (null === $condition) {
            return true;
        }

        // M1 supports the "true" operator (the FieldMapping::onlyIf shorthand). The full operator
        // vocabulary is wired in M6.
        if ('true' !== $condition['operator']) {
            return true;
        }

        $field = $condition['field'];
        $value = isset($availableFields[$field]) ? $availableFields[$field]->getResolver()->resolve($entity, $context) : null;

        return (bool) $value;
    }

    /**
     * @return list<FieldMapping>
     */
    private function resolveMappings(FeedInterface $feed, FeedSourceInterface $source): array
    {
        // M1 derives the mapping from the matching preset; admin-editable FeedField rows land in M3.
        foreach ($this->mappingPresetRegistry->forFeedType((string) $source->getFeedType()) as $preset) {
            if ($preset->getFormat() === $feed->getFormat()) {
                return $preset->getMapping();
            }
        }

        return [];
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
     * @return array<string, string>
     */
    private function buildPreamble(FeedInterface $feed, FeedContext $context): array
    {
        $title = (string) $feed->getCode();

        $link = '';
        $channel = $context->getChannel();
        if (null !== $channel && null !== $channel->getHostname()) {
            $link = 'https://' . $channel->getHostname();
        }

        return ['title' => $title, 'link' => $link, 'description' => $title];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return list<string>
     */
    private function requiredFields(array $config): array
    {
        $fields = [];
        if (isset($config['requiredFields']) && is_array($config['requiredFields'])) {
            foreach ($config['requiredFields'] as $field) {
                if (is_string($field)) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
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
        if (!is_resource($stream)) {
            throw new \RuntimeException('Could not open a temporary stream');
        }

        return $stream;
    }
}
