<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Setono\SyliusFeedPlugin\DataProvider\DataProviderInterface;
use Setono\SyliusFeedPlugin\FeedContext\FeedContextInterface;
use Setono\SyliusFeedPlugin\FeedContext\ItemContextInterface;

final class FeedType implements FeedTypeInterface
{
    /** @var string */
    private $code;

    /** @var string */
    private $template;

    /** @var DataProviderInterface */
    private $dataProvider;

    /** @var FeedContextInterface */
    private $feedContext;

    /** @var ItemContextInterface */
    private $itemContext;

    public function __construct(
        string $code,
        string $template,
        DataProviderInterface $dataProvider,
        FeedContextInterface $feedContext,
        ItemContextInterface $itemContext
    ) {
        $this->code = $code;
        $this->template = $template;
        $this->dataProvider = $dataProvider;
        $this->feedContext = $feedContext;
        $this->itemContext = $itemContext;
    }

    public function __toString(): string
    {
        return $this->getCode();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getDataProvider(): DataProviderInterface
    {
        return $this->dataProvider;
    }

    public function getFeedContext(): FeedContextInterface
    {
        return $this->feedContext;
    }

    public function getItemContext(): ItemContextInterface
    {
        return $this->itemContext;
    }
}
