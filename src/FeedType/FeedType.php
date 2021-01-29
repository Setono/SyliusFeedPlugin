<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Setono\SyliusFeedPlugin\DataProvider\DataProviderInterface;
use Setono\SyliusFeedPlugin\FeedContext\FeedContextInterface;
use Setono\SyliusFeedPlugin\FeedContext\ItemContextInterface;
use Symfony\Component\Validator\Constraint;

final class FeedType implements FeedTypeInterface
{
    private string $code;

    private string $template;

    private DataProviderInterface $dataProvider;

    private FeedContextInterface $feedContext;

    private ItemContextInterface $itemContext;

    private array $validationGroups;

    public function __construct(
        string $code,
        string $template,
        DataProviderInterface $dataProvider,
        FeedContextInterface $feedContext,
        ItemContextInterface $itemContext,
        array $validationGroups = []
    ) {
        $this->code = $code;
        $this->template = $template;
        $this->dataProvider = $dataProvider;
        $this->feedContext = $feedContext;
        $this->itemContext = $itemContext;
        $this->validationGroups = count($validationGroups) === 0 ? [Constraint::DEFAULT_GROUP] : $validationGroups;
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

    public function getValidationGroups(): array
    {
        return $this->validationGroups;
    }
}
