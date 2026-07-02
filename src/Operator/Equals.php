<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * Loose equality: scalars are compared as strings, everything else strictly.
 */
final class Equals implements OperatorInterface
{
    public function getName(): string
    {
        return 'equals';
    }

    public function matches(mixed $value, array $params): bool
    {
        return $this->looseEquals($value, $params['value'] ?? null);
    }

    private function looseEquals(mixed $a, mixed $b): bool
    {
        if (is_scalar($a) && is_scalar($b)) {
            return (string) $a === (string) $b;
        }

        return $a === $b;
    }
}
