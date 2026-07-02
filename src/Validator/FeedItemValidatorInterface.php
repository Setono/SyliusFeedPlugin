<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Validator;

use Setono\SyliusFeedPlugin\Item\FeedItem;

interface FeedItemValidatorInterface
{
    /**
     * Validate a per-item object against both its typed-item Symfony constraints (e.g. the Google
     * Shopping spec on {@see \Setono\SyliusFeedPlugin\Item\Google\GoogleShoppingItem}) and the
     * generic required-field fallback for the given format (§11).
     *
     * The typed constraints only run when $validationGroups is non-empty (the format opts in via
     * {@see \Setono\SyliusFeedPlugin\Format\FormatInterface::getItemValidationGroups()}), so a typed
     * item rendered under a non-matching format is not spuriously rejected; the required-field
     * fallback always runs.
     *
     * @param list<string> $requiredFields
     * @param list<string> $validationGroups
     *
     * @return list<string> the (deduplicated) violation messages / translation keys; empty when valid
     */
    public function validate(FeedItem $item, array $requiredFields, array $validationGroups = ['Default']): array;
}
