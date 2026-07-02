<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Validator;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates a per-item object for feed inclusion (§11): it runs the item's typed Symfony
 * constraints (a typed subclass like GoogleShoppingItem carries the spec's constraints; a plain
 * FeedItem has none) and, on top of that, the generic required-field fallback for the format. The
 * two are merged and deduplicated into a flat list of violation messages / translation keys.
 */
final class FeedItemValidator implements FeedItemValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly RequiredFieldsValidatorInterface $requiredFieldsValidator,
    ) {
    }

    public function validate(FeedItem $item, array $requiredFields, array $validationGroups = ['Default']): array
    {
        $messages = [];

        // Only run the typed constraints when the format opted in; otherwise a typed item (e.g. a
        // GoogleShoppingItem produced by the product_variant source) rendered under a non-Google
        // format would be rejected for lacking g:* fields it was never meant to carry.
        if ([] !== $validationGroups) {
            foreach ($this->validator->validate($item, null, $validationGroups) as $violation) {
                $template = $violation->getMessageTemplate();
                $messages[] = '' !== $template ? $template : (string) $violation->getMessage();
            }
        }

        foreach ($this->requiredFieldsValidator->findMissingFields($item, $requiredFields) as $field) {
            $messages[] = 'setono_sylius_feed.validation.missing_field: ' . $field;
        }

        return array_values(array_unique($messages));
    }
}
