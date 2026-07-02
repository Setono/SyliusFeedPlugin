<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Formats a `\DateTimeInterface` (or a parseable date string) using a PHP date format. Maps
 * element-wise over a list; anything that is not a `\DateTimeInterface` or a non-empty parseable
 * string (or an invalid `format`/`timezone` param) is a no-op — the original value is returned
 * unchanged (§10).
 */
final class DateFormat implements TransformationInterface
{
    public const TYPE = 'date_format';

    public static function of(string $format, ?string $timezone = null): TransformationConfig
    {
        $params = ['format' => $format];

        if (null !== $timezone) {
            $params['timezone'] = $timezone;
        }

        return new TransformationConfig(self::TYPE, $params);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $element): mixed => $this->format($element, $params), $value);
        }

        return $this->format($value, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function format(mixed $value, array $params): mixed
    {
        $format = $params['format'] ?? null;
        if (!is_string($format) || '' === $format) {
            return $value;
        }

        $date = $this->toDateTime($value);
        if (null === $date) {
            return $value;
        }

        $timezone = $params['timezone'] ?? null;
        if (is_string($timezone) && '' !== $timezone) {
            try {
                $date = $date->setTimezone(new \DateTimeZone($timezone));
            } catch (\Exception) {
                return $value;
            }
        }

        return $date->format($format);
    }

    private function toDateTime(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (!is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
