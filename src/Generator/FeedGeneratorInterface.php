<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Model\FeedInterface;

interface FeedGeneratorInterface
{
    /**
     * Will generate the files for the given feed
     *
     * @param FeedInterface $feed
     */
    public function generate(FeedInterface $feed): void;
}
