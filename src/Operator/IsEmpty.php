<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * True only for `null`, an empty string, or an empty array. `0`, `'0'` and `false` are NOT empty.
 */
final class IsEmpty implements OperatorInterface
{
    public function getName(): string
    {
        return 'empty';
    }

    public function matches(mixed $value, array $params): bool
    {
        return null === $value || '' === $value || [] === $value;
    }
}
