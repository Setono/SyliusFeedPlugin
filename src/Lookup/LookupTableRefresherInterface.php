<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use Setono\SyliusFeedPlugin\Model\LookupTableInterface;

/**
 * Re-imports a LookupTable's rows from its source (§10.1). A failed refresh keeps the last-good
 * rows — a transient outage or a malformed download must never blank out the serving data.
 */
interface LookupTableRefresherInterface
{
    public function refresh(LookupTableInterface $table): void;
}
