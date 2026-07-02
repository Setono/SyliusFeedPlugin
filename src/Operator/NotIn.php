<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * Logical negation of {@see In}, using the same loose-equality rule.
 */
final class NotIn implements OperatorInterface
{
    public function getName(): string
    {
        return 'not_in';
    }

    public function matches(mixed $value, array $params): bool
    {
        $operand = $params['value'] ?? null;
        $list = is_array($operand) ? $operand : [$operand];

        foreach ($list as $item) {
            if ($this->looseEquals($value, $item)) {
                return false;
            }
        }

        return true;
    }

    private function looseEquals(mixed $a, mixed $b): bool
    {
        if (is_scalar($a) && is_scalar($b)) {
            return (string) $a === (string) $b;
        }

        return $a === $b;
    }
}
