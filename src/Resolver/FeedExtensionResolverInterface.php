<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Resolver;

use Setono\SyliusFeedPlugin\Model\FeedInterface;

interface FeedExtensionResolverInterface
{
    public function resolve(FeedInterface $feed): string;
}
