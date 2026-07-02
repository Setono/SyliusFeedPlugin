<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * Falsiness check, the counterpart to {@see IsTrue}.
 */
final class IsFalse implements OperatorInterface
{
    public function getName(): string
    {
        return 'false';
    }

    public function matches(mixed $value, array $params): bool
    {
        return false === (bool) $value;
    }
}
