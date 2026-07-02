<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\LookupTableInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<LookupTableInterface>
 */
interface LookupTableRepositoryInterface extends RepositoryInterface
{
    public function findOneByCode(string $code): ?LookupTableInterface;
}
