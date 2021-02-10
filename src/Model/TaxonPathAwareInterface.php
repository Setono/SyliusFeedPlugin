<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Locale\Model\LocaleInterface;

interface TaxonPathAwareInterface
{
    // In cases when some root taxon assigned to channel's menuTaxon,
    // we should exclude root taxon from path
    public function getTaxonPath(LocaleInterface $locale, bool $excludeRoot = false): ?string;
}
