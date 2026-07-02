<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Mapping;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;

/**
 * Resolves the ordered field mappings for one source of a feed (§10). The admin-editable FeedField
 * rows are the source of truth once a source has any; the matching MappingPreset is the fallback
 * for a source that was never seeded/edited. Shared by the generator and the dry-run preview so the
 * two never diverge.
 */
interface MappingResolverInterface
{
    /**
     * @return list<FieldMapping>
     */
    public function resolve(FeedInterface $feed, FeedSourceInterface $source): array;
}
