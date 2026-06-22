<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Validator;

use Setono\SyliusFeedPlugin\Item\FeedItem;

/**
 * The generic required-field check used when a feed type has no typed-item constraints (§11):
 * an item is invalid if any required output field is absent or empty. Full typed-item Symfony
 * Validator constraints land in M6.
 */
final class RequiredFieldsValidator implements RequiredFieldsValidatorInterface
{
    /**
     * @param list<string> $requiredFields
     *
     * @return list<string> the required fields that are missing or empty
     */
    public function findMissingFields(FeedItem $item, array $requiredFields): array
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            $value = $item->get($field);
            if (null === $value || '' === $value || [] === $value) {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}
