<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class FeedRepository extends EntityRepository implements FeedRepositoryInterface
{
    public function findEnabled(): array
    {
        $objs = $this->findBy([
            'enabled' => true,
        ]);

        Assert::allIsInstanceOf($objs, FeedInterface::class);

        return $objs;
    }
}
