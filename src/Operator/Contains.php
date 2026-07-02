<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * For an array value, true if the operand loose-equals any element. For a string value, true if
 * the operand is a substring. Anything else is false.
 */
final class Contains implements OperatorInterface
{
    public function getName(): string
    {
        return 'contains';
    }

    public function matches(mixed $value, array $params): bool
    {
        $operand = $params['value'] ?? null;

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->looseEquals($item, $operand)) {
                    return true;
                }
            }

            return false;
        }

        if (is_string($value)) {
            return str_contains($value, is_scalar($operand) ? (string) $operand : '');
        }

        return false;
    }

    private function looseEquals(mixed $a, mixed $b): bool
    {
        if (is_scalar($a) && is_scalar($b)) {
            return (string) $a === (string) $b;
        }

        return $a === $b;
    }
}
