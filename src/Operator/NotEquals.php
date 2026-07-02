<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * Logical negation of {@see Equals}, using the same loose-equality rule.
 */
final class NotEquals implements OperatorInterface
{
    public function getName(): string
    {
        return 'not_equals';
    }

    public function matches(mixed $value, array $params): bool
    {
        return !$this->looseEquals($value, $params['value'] ?? null);
    }

    private function looseEquals(mixed $a, mixed $b): bool
    {
        if (is_scalar($a) && is_scalar($b)) {
            return (string) $a === (string) $b;
        }

        return $a === $b;
    }
}
