<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Model\FeedInterface;

interface FeedPartGeneratorInterface
{
    /**
     * Generates part of a feed with the given objects and returns the path where the generated part can be retrieved
     *
     * @param iterable<object> $objects
     *
     * @return string the readable path from the filesystem you have configured
     */
    public function generate(FeedInterface $feed, iterable $objects): string;
}
