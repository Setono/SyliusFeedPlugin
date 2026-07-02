<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * Numeric inclusive range check. The operand is a 2-element list `[min, max]` or a map with
 * `min`/`max` keys. False if the value or either bound is not numeric, or the operand shape is
 * invalid.
 */
final class Between implements OperatorInterface
{
    public function getName(): string
    {
        return 'between';
    }

    public function matches(mixed $value, array $params): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        [$min, $max] = $this->bounds($params['value'] ?? null);

        if (!is_numeric($min) || !is_numeric($max)) {
            return false;
        }

        $number = (float) $value;

        return (float) $min <= $number && $number <= (float) $max;
    }

    /**
     * @return array{0: mixed, 1: mixed}
     */
    private function bounds(mixed $operand): array
    {
        if (!is_array($operand)) {
            return [null, null];
        }

        if (array_key_exists('min', $operand) && array_key_exists('max', $operand)) {
            return [$operand['min'], $operand['max']];
        }

        $values = array_values($operand);
        if (2 === count($values)) {
            return [$values[0], $values[1]];
        }

        return [null, null];
    }
}
