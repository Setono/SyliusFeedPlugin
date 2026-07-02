<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Preview;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Event\FeedItemBuiltEvent;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Filter\FilterEvaluatorInterface;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Generator\FieldMappingEvaluatorInterface;
use Setono\SyliusFeedPlugin\Generator\SourceResolverBinder;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Lookup\LookupReferenceResolverInterface;
use Setono\SyliusFeedPlugin\Mapping\MappingResolverInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Setono\SyliusFeedPlugin\Validator\FeedItemValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @see PreviewServiceInterface
 *
 * Runs the exact pipeline of {@see \Setono\SyliusFeedPlugin\Generator\FeedGenerator} (same mapping
 * resolution, filter evaluation, item-built event and validation) but never opens a writer and
 * never touches storage — the item output stays in memory as a diagnostic sample (§11).
 */
final class PreviewService implements PreviewServiceInterface
{
    /**
     * How many source items previewItem() scans per source while looking for a matching id — a
     * bound so the single-item tester cannot walk an unbounded catalog.
     */
    private const PREVIEW_ITEM_SCAN_CAP = 1000;

    public function __construct(
        private readonly FeedTypeRegistryInterface $feedTypeRegistry,
        private readonly MappingResolverInterface $mappingResolver,
        private readonly FormatRegistryInterface $formatRegistry,
        private readonly FieldMappingEvaluatorInterface $fieldMappingEvaluator,
        private readonly FilterEvaluatorInterface $filterEvaluator,
        private readonly LookupReferenceResolverInterface $lookupReferenceResolver,
        private readonly FeedItemValidatorInterface $feedItemValidator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function preview(FeedInterface $feed, FeedContext $context, int $limit = 50): PreviewResult
    {
        $limit = max(1, $limit);

        $format = $this->formatRegistry->get((string) $feed->getFormat());
        $requiredFields = $format->getRequiredFields();
        $itemValidationGroups = $format->getItemValidationGroups();

        $sampled = 0;
        $preExcluded = 0;
        $postExcluded = 0;
        // The item-built event (skip) and validation both drop after mapping — grouped as one stage.
        $mappingValidationExcluded = 0;
        $includedCount = 0;

        $included = [];
        $excluded = [];

        foreach ($this->sortedSources($feed) as $feedSource) {
            $feedType = $this->feedTypeRegistry->get((string) $feedSource->getFeedType());
            $availableFields = $feedType->getAvailableFields();
            $mappings = $this->mappingResolver->resolve($feed, $feedSource);
            $filterSet = new FilterSet($feedSource->getFilters());

            $sampledFromSource = 0;
            foreach ($feedType->getDataSource()->getItems($context, $filterSet) as $entity) {
                if ($sampledFromSource >= $limit) {
                    break;
                }
                ++$sampledFromSource;
                ++$sampled;

                $item = $feedType->createItem($entity, $context);
                SourceResolverBinder::bind($item, $availableFields, $this->lookupReferenceResolver);

                $excludingPreFilter = $this->filterEvaluator->excludedBy($item, $filterSet->getPreFilters());
                if (null !== $excludingPreFilter) {
                    ++$preExcluded;
                    $excluded[] = ['item' => $this->resolveItemIdentifier($item), 'reason' => sprintf('filter:pre:%s', (string) $excludingPreFilter->getField())];

                    continue;
                }

                $this->fieldMappingEvaluator->apply($item, $mappings, $availableFields);

                $excludingPostFilter = $this->filterEvaluator->excludedBy($item, $filterSet->getPostFilters());
                if (null !== $excludingPostFilter) {
                    ++$postExcluded;
                    $excluded[] = ['item' => $this->resolveItemIdentifier($item), 'reason' => sprintf('filter:post:%s', (string) $excludingPostFilter->getField())];

                    continue;
                }

                $this->eventDispatcher->dispatch(new FeedItemBuiltEvent($item));

                if ($item->isSkipped()) {
                    ++$mappingValidationExcluded;
                    $excluded[] = ['item' => $this->resolveItemIdentifier($item), 'reason' => 'skipped'];

                    continue;
                }

                $violations = $this->feedItemValidator->validate($item, $requiredFields, $itemValidationGroups);
                if ([] !== $violations) {
                    ++$mappingValidationExcluded;
                    $excluded[] = ['item' => $this->resolveItemIdentifier($item), 'reason' => 'validation:' . implode('; ', $violations)];

                    continue;
                }

                ++$includedCount;
                $included[] = $item->all();
            }
        }

        $afterPreFilters = $sampled - $preExcluded;
        $afterMappingValidation = $afterPreFilters - $mappingValidationExcluded;
        $afterPostFilters = $afterMappingValidation - $postExcluded;

        $funnel = new PreviewFunnel($sampled, $afterPreFilters, $afterMappingValidation, $afterPostFilters, $includedCount);

        return new PreviewResult(
            $funnel,
            array_slice($included, 0, $limit),
            array_slice($excluded, 0, $limit),
        );
    }

    public function previewItem(FeedInterface $feed, FeedContext $context, string $id): array
    {
        $format = $this->formatRegistry->get((string) $feed->getFormat());
        $requiredFields = $format->getRequiredFields();
        $itemValidationGroups = $format->getItemValidationGroups();

        foreach ($this->sortedSources($feed) as $feedSource) {
            $feedType = $this->feedTypeRegistry->get((string) $feedSource->getFeedType());
            $availableFields = $feedType->getAvailableFields();
            $mappings = $this->mappingResolver->resolve($feed, $feedSource);
            $filterSet = new FilterSet($feedSource->getFilters());

            $scanned = 0;
            foreach ($feedType->getDataSource()->getItems($context, $filterSet) as $entity) {
                if ($scanned >= self::PREVIEW_ITEM_SCAN_CAP) {
                    break;
                }
                ++$scanned;

                $item = $feedType->createItem($entity, $context);
                SourceResolverBinder::bind($item, $availableFields, $this->lookupReferenceResolver);

                // The pre-filter is evaluated before mapping in the real pipeline, but we always map
                // so the output id resolves and we can match even a pre-filtered item.
                $excludingPreFilter = $this->filterEvaluator->excludedBy($item, $filterSet->getPreFilters());
                $this->fieldMappingEvaluator->apply($item, $mappings, $availableFields);

                if ($this->resolveItemIdentifier($item) !== $id) {
                    continue;
                }

                if (null !== $excludingPreFilter) {
                    return $this->itemVerdict(false, $item->all(), sprintf('filter:pre:%s', (string) $excludingPreFilter->getField()));
                }

                $excludingPostFilter = $this->filterEvaluator->excludedBy($item, $filterSet->getPostFilters());
                if (null !== $excludingPostFilter) {
                    return $this->itemVerdict(false, $item->all(), sprintf('filter:post:%s', (string) $excludingPostFilter->getField()));
                }

                $this->eventDispatcher->dispatch(new FeedItemBuiltEvent($item));

                if ($item->isSkipped()) {
                    return $this->itemVerdict(false, $item->all(), 'skipped');
                }

                $violations = $this->feedItemValidator->validate($item, $requiredFields, $itemValidationGroups);
                if ([] !== $violations) {
                    return $this->itemVerdict(false, $item->all(), 'validation:' . implode('; ', $violations));
                }

                return $this->itemVerdict(true, $item->all(), null);
            }
        }

        return $this->itemVerdict(false, [], 'not_found');
    }

    /**
     * @param array<string, mixed> $output
     *
     * @return array{included: bool, output: array<string, mixed>, reason: ?string}
     */
    private function itemVerdict(bool $included, array $output, ?string $reason): array
    {
        return ['included' => $included, 'output' => $output, 'reason' => $reason];
    }

    /**
     * The item's identity for reporting: its output id if the mapping has produced one, else null
     * (mirrors the generator, §11).
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
}
