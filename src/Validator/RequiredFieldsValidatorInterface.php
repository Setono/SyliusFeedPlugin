<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Validator;

use Setono\SyliusFeedPlugin\Item\FeedItem;

interface RequiredFieldsValidatorInterface
{
    /**
     * @param list<string> $requiredFields
     *
     * @return list<string> the required fields that are missing or empty
     */
    public function findMissingFields(FeedItem $item, array $requiredFields): array;
}
