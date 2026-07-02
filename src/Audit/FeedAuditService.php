<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Audit;

use Setono\SyliusFeedPlugin\Preview\PreviewResult;

/**
 * @see FeedAuditServiceInterface
 */
final class FeedAuditService implements FeedAuditServiceInterface
{
    /**
     * Google Shopping caps the title at 150 characters; longer titles are truncated by the channel.
     */
    private const MAX_TITLE_LENGTH = 150;

    /** @var list<string> */
    private const TITLE_FIELDS = ['g:title', 'title'];

    /** @var list<string> */
    private const DESCRIPTION_FIELDS = ['g:description', 'description'];

    /** @var list<string> */
    private const PRICE_FIELDS = ['g:price', 'price'];

    /** @var list<string> */
    private const AVAILABILITY_FIELDS = ['g:availability', 'availability'];

    public function audit(PreviewResult $result): AuditReport
    {
        $bags = $result->included;

        return new AuditReport(
            $this->fillRates($bags),
            $this->warnings($bags),
            $this->distributions($bags),
        );
    }

    /**
     * @param list<array<string, mixed>> $bags
     *
     * @return array<string, float>
     */
    private function fillRates(array $bags): array
    {
        $total = count($bags);
        if (0 === $total) {
            return [];
        }

        $fields = [];
        foreach ($bags as $bag) {
            foreach (array_keys($bag) as $field) {
                if (!in_array($field, $fields, true)) {
                    $fields[] = $field;
                }
            }
        }

        $fillRates = [];
        foreach ($fields as $field) {
            $filled = 0;
            foreach ($bags as $bag) {
                if (array_key_exists($field, $bag) && !$this->isEmpty($bag[$field])) {
                    ++$filled;
                }
            }

            $fillRates[$field] = (float) $filled / $total;
        }

        return $fillRates;
    }

    /**
     * @param list<array<string, mixed>> $bags
     *
     * @return list<array{type: string, field: string, count: int}>
     */
    private function warnings(array $bags): array
    {
        $warnings = [];

        $titleField = $this->firstPresentField($bags, self::TITLE_FIELDS);
        if (null !== $titleField) {
            $count = $this->countMatching($bags, $titleField, static fn (mixed $value): bool => is_string($value) && mb_strlen($value) > self::MAX_TITLE_LENGTH);
            if ($count > 0) {
                $warnings[] = ['type' => 'title_too_long', 'field' => $titleField, 'count' => $count];
            }
        }

        $descriptionField = $this->firstPresentField($bags, self::DESCRIPTION_FIELDS);
        if (null !== $descriptionField) {
            $count = $this->countMatching($bags, $descriptionField, static fn (mixed $value): bool => is_string($value) && 1 === preg_match('/<[^>]+>/', $value));
            if ($count > 0) {
                $warnings[] = ['type' => 'description_contains_html', 'field' => $descriptionField, 'count' => $count];
            }
        }

        $priceField = $this->firstPresentField($bags, self::PRICE_FIELDS);
        if (null !== $priceField) {
            $count = $this->countMatching($bags, $priceField, fn (mixed $value): bool => $this->isPriceEmpty($value));
            if ($count > 0) {
                $warnings[] = ['type' => 'price_empty', 'field' => $priceField, 'count' => $count];
            }
        }

        return $warnings;
    }

    /**
     * @param list<array<string, mixed>> $bags
     *
     * @return array<string, array<string, int>>
     */
    private function distributions(array $bags): array
    {
        $distributions = [];

        $availabilityField = $this->firstPresentField($bags, self::AVAILABILITY_FIELDS);
        if (null !== $availabilityField) {
            $counts = [];
            foreach ($bags as $bag) {
                $value = $bag[$availabilityField] ?? null;
                if (is_scalar($value)) {
                    $key = (string) $value;
                    $counts[$key] = ($counts[$key] ?? 0) + 1;
                }
            }

            if ([] !== $counts) {
                $distributions[$availabilityField] = $counts;
            }
        }

        $priceField = $this->firstPresentField($bags, self::PRICE_FIELDS);
        if (null !== $priceField) {
            $zero = 0;
            $nonZero = 0;
            foreach ($bags as $bag) {
                if (!array_key_exists($priceField, $bag)) {
                    continue;
                }

                if ($this->isPriceEmpty($bag[$priceField])) {
                    ++$zero;
                } else {
                    ++$nonZero;
                }
            }

            if ($zero + $nonZero > 0) {
                $distributions[$priceField] = ['zero' => $zero, 'non_zero' => $nonZero];
            }
        }

        return $distributions;
    }

    /**
     * @param list<array<string, mixed>> $bags
     * @param callable(mixed): bool $matches
     */
    private function countMatching(array $bags, string $field, callable $matches): int
    {
        $count = 0;
        foreach ($bags as $bag) {
            if ($matches($bag[$field] ?? null)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * The first of the candidate keys that appears in any bag — resolves the logical concept (title,
     * price, …) to the concrete output field the feed actually uses (`g:title` vs `title`, …).
     *
     * @param list<array<string, mixed>> $bags
     * @param list<string> $candidates
     */
    private function firstPresentField(array $bags, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            foreach ($bags as $bag) {
                if (array_key_exists($candidate, $bag)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function isEmpty(mixed $value): bool
    {
        return null === $value || '' === $value || [] === $value;
    }

    /**
     * Advisory: a price is "empty" when it is missing, empty, or resolves to a zero amount — a
     * leading numeric of 0 in a formatted string like "0.00 USD" counts as zero.
     */
    private function isPriceEmpty(mixed $value): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        if (is_int($value) || is_float($value)) {
            return 0.0 === (float) $value;
        }

        if (is_string($value)) {
            if (1 === preg_match('/-?\d+(?:[.,]\d+)?/', $value, $matches)) {
                return 0.0 === (float) str_replace(',', '.', $matches[0]);
            }

            return true;
        }

        return false;
    }
}
