<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * Regex match. The operand is a full PCRE pattern string including delimiters (e.g. `/^SKU/i`).
 * False if the value is not scalar, the operand is not a non-empty string, or the pattern is
 * invalid.
 */
final class Matches implements OperatorInterface
{
    public function getName(): string
    {
        return 'matches';
    }

    public function matches(mixed $value, array $params): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        $operand = $params['value'] ?? null;

        if (!is_string($operand) || '' === $operand) {
            return false;
        }

        return 1 === @preg_match($operand, (string) $value);
    }
}
