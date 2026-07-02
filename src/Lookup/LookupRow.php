<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

/**
 * Normalises a parsed source record into a `column => scalar|null` row (§10.1), string-keying the
 * columns and dropping non-scalar values.
 */
final class LookupRow
{
    /**
     * @return array<string, scalar|null>
     */
    public static function normalize(mixed $record): array
    {
        if (!is_iterable($record)) {
            return [];
        }

        $row = [];
        foreach ($record as $column => $value) {
            if (!is_scalar($column)) {
                continue;
            }

            $row[(string) $column] = is_scalar($value) ? $value : null;
        }

        return $row;
    }
}
