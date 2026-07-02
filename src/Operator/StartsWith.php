<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * String prefix check; false unless the value is a string.
 */
final class StartsWith implements OperatorInterface
{
    public function getName(): string
    {
        return 'starts_with';
    }

    public function matches(mixed $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $operand = $params['value'] ?? null;

        return str_starts_with($value, is_scalar($operand) ? (string) $operand : '');
    }
}
