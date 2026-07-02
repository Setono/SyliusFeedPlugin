<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\LookupTableInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class LookupTableRepository extends EntityRepository implements LookupTableRepositoryInterface
{
    public function findOneByCode(string $code): ?LookupTableInterface
    {
        $result = $this->findOneBy(['code' => $code]);

        return $result instanceof LookupTableInterface ? $result : null;
    }
}
