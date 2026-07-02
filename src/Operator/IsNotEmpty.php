<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * Logical negation of {@see IsEmpty}.
 */
final class IsNotEmpty implements OperatorInterface
{
    public function getName(): string
    {
        return 'not_empty';
    }

    public function matches(mixed $value, array $params): bool
    {
        return !(null === $value || '' === $value || [] === $value);
    }
}
