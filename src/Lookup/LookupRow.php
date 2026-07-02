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
     * @param iterable<int|string, mixed> $record
     *
     * @return array<string, scalar|null>
     */
    public static function normalize(iterable $record): array
    {
        $row = [];
        foreach ($record as $column => $value) {
            $row[(string) $column] = is_scalar($value) ? $value : null;
        }

        return $row;
    }
}
