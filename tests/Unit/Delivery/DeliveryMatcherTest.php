<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Delivery;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Delivery\DeliveryMatcher;

final class DeliveryMatcherTest extends TestCase
{
    private DeliveryMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new DeliveryMatcher();
    }

    /**
     * @test
     */
    public function it_matches_all_contexts_for_an_empty_match(): void
    {
        self::assertTrue($this->matcher->matches([], 'web', 'en_US', 'USD'));
        self::assertTrue($this->matcher->matches([], null, null, null));
    }

    /**
     * @test
     */
    public function it_matches_on_a_single_channel_dimension(): void
    {
        self::assertTrue($this->matcher->matches(['channel' => 'web'], 'web', 'da_DK', 'DKK'));
        self::assertFalse($this->matcher->matches(['channel' => 'web'], 'mobile', 'da_DK', 'DKK'));
    }

    /**
     * @test
     */
    public function it_requires_every_present_dimension_to_match(): void
    {
        $match = ['channel' => 'web', 'locale' => 'en_US', 'currency' => 'USD'];

        self::assertTrue($this->matcher->matches($match, 'web', 'en_US', 'USD'));
        self::assertFalse($this->matcher->matches($match, 'web', 'en_US', 'EUR'));
        self::assertFalse($this->matcher->matches($match, 'web', 'da_DK', 'USD'));
    }

    /**
     * @test
     */
    public function it_treats_omitted_and_empty_dimensions_as_wildcards(): void
    {
        // locale omitted -> wildcard, currency empty -> wildcard, only channel constrains.
        $match = ['channel' => 'web', 'currency' => ''];

        self::assertTrue($this->matcher->matches($match, 'web', 'en_US', 'USD'));
        self::assertTrue($this->matcher->matches($match, 'web', 'da_DK', 'DKK'));
        self::assertFalse($this->matcher->matches($match, 'mobile', 'en_US', 'USD'));
    }

    /**
     * @test
     */
    public function it_does_not_match_a_null_code_against_a_present_constraint(): void
    {
        self::assertFalse($this->matcher->matches(['channel' => 'web'], null, null, null));
    }
}
