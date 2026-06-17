<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Context;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Sylius\Component\Core\Model\ChannelInterface;

final class FeedContextTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_builds_a_key_from_all_present_dimensions(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('default');

        $context = new FeedContext($channel->reveal(), 'en_US', 'USD');

        self::assertSame('default_en_us_usd', $context->key());
        self::assertSame('en_US', $context->getLocale());
        self::assertSame('USD', $context->getCurrencyCode());
        self::assertSame($channel->reveal(), $context->getChannel());
    }

    /**
     * @test
     */
    public function it_builds_a_key_from_the_present_dimensions_only(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('web');

        self::assertSame('web_en_us', (new FeedContext($channel->reveal(), 'en_US'))->key());
        self::assertSame('web', (new FeedContext($channel->reveal()))->key());
        self::assertSame('da_dk', (new FeedContext(null, 'da_DK'))->key());
    }

    /**
     * @test
     */
    public function it_falls_back_to_a_default_key_when_no_dimensions_are_present(): void
    {
        self::assertSame('default', (new FeedContext())->key());
    }
}
