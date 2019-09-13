<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Setono\SyliusFeedPlugin\DataProvider\DataProviderInterface;
use Setono\SyliusFeedPlugin\FeedContext\FeedContextInterface;
use Setono\SyliusFeedPlugin\FeedContext\ItemContextInterface;

interface FeedTypeInterface
{
    public function __toString(): string;

    public function getCode(): string;

    public function getTemplate(): string;

    public function getDataProvider(): DataProviderInterface;

    public function getFeedContext(): FeedContextInterface;

    public function getItemContext(): ItemContextInterface;

    /**
     * The validation groups used when validating each item
     */
    public function getValidationGroups(): array;
}
