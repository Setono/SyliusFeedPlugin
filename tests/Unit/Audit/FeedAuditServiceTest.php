<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Audit;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Audit\FeedAuditService;
use Setono\SyliusFeedPlugin\Preview\PreviewFunnel;
use Setono\SyliusFeedPlugin\Preview\PreviewResult;

/**
 * The audit is purely advisory: over a known set of included output bags it computes per-field fill
 * rates, flags soft quality warnings (a > 150-char title, a zero price), and counts value
 * distributions (availability) — without excluding anything.
 */
final class FeedAuditServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_computes_fill_rates_soft_warnings_and_distributions(): void
    {
        $longTitle = str_repeat('a', 151);

        $result = new PreviewResult(
            new PreviewFunnel(3, 3, 3, 3, 3),
            [
                ['g:id' => 'SKU-1', 'g:title' => $longTitle, 'g:gtin' => '123', 'g:availability' => 'in_stock', 'g:price' => '9.99 USD'],
                ['g:id' => 'SKU-2', 'g:title' => 'Short', 'g:availability' => 'in_stock', 'g:price' => '0.00 USD'],
                ['g:id' => 'SKU-3', 'g:title' => 'Also short', 'g:gtin' => '', 'g:availability' => 'out_of_stock', 'g:price' => '5.00 USD'],
            ],
            [],
        );

        $report = (new FeedAuditService())->audit($result);

        // fill rates: g:id is present and non-empty in all 3 items; g:gtin only in 1 of 3
        self::assertSame(1.0, $report->fillRates['g:id']);
        self::assertEqualsWithDelta(1 / 3, $report->fillRates['g:gtin'], 0.0001);

        // soft warnings: exactly one over-long title and exactly one zero price
        self::assertContains(['type' => 'title_too_long', 'field' => 'g:title', 'count' => 1], $report->warnings);
        self::assertContains(['type' => 'price_empty', 'field' => 'g:price', 'count' => 1], $report->warnings);

        // distribution: availability value counts
        self::assertSame(['in_stock' => 2, 'out_of_stock' => 1], $report->distributions['g:availability']);
    }

    /**
     * @test
     */
    public function it_returns_empty_reports_when_nothing_is_included(): void
    {
        $result = new PreviewResult(new PreviewFunnel(0, 0, 0, 0, 0), [], []);

        $report = (new FeedAuditService())->audit($result);

        self::assertSame([], $report->fillRates);
        self::assertSame([], $report->warnings);
        self::assertSame([], $report->distributions);
    }
}
