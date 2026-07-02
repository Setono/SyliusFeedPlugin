<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * True if the value loose-equals any element of the operand list. A non-array operand is treated
 * as a single-element list.
 */
final class In implements OperatorInterface
{
    public function getName(): string
    {
        return 'in';
    }

    public function matches(mixed $value, array $params): bool
    {
        $operand = $params['value'] ?? null;
        $list = is_array($operand) ? $operand : [$operand];

        foreach ($list as $item) {
            if ($this->looseEquals($value, $item)) {
                return true;
            }
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
