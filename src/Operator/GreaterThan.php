<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * Numeric comparison; returns false unless both the value and the operand are numeric.
 */
final class GreaterThan implements OperatorInterface
{
    public function getName(): string
    {
        return 'gt';
    }

    public function matches(mixed $value, array $params): bool
    {
        $operand = $params['value'] ?? null;

        if (!is_numeric($value) || !is_numeric($operand)) {
            return false;
        }

        return (float) $value > (float) $operand;
    }
}
