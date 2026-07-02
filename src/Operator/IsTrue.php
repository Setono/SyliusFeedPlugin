<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * Truthiness check, used by the `FeedField::onlyIf` shorthand to emit a field only when its
 * value is truthy.
 */
final class IsTrue implements OperatorInterface
{
    public function getName(): string
    {
        return 'true';
    }

    public function matches(mixed $value, array $params): bool
    {
        return true === (bool) $value;
    }
}
