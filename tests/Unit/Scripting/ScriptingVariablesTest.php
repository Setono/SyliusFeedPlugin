<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Scripting;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Scripting\ScriptingVariables;
use Sylius\Component\Core\Model\ChannelInterface;

final class ScriptingVariablesTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_builds_the_variable_set_from_the_item_and_the_value(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $entity = new \stdClass();
        $context = new FeedContext($channel, 'en_US', 'USD');

        $item = new FeedItem($entity, $context);
        $item->set('g:title', 'Blue shoe');
        $item->set('g:price', '10.00 USD');

        $variables = ScriptingVariables::for($item, 'the value');

        self::assertSame([
            'value' => 'the value',
            'entity' => $entity,
            'channel' => $channel,
            'locale' => 'en_US',
            'currency' => 'USD',
            'fields' => [
                'g:title' => 'Blue shoe',
                'g:price' => '10.00 USD',
            ],
        ], $variables);
    }

    /**
     * @test
     */
    public function it_reflects_the_output_bag_at_call_time(): void
    {
        $item = new FeedItem(new \stdClass(), new FeedContext());
        $item->set('g:title', 'Blue shoe');

        self::assertSame(
            ['g:title' => 'Blue shoe'],
            ScriptingVariables::for($item, null)['fields'],
        );

        $item->set('g:brand', 'Acme');

        self::assertSame(
            ['g:title' => 'Blue shoe', 'g:brand' => 'Acme'],
            ScriptingVariables::for($item, null)['fields'],
        );
    }

    /**
     * @test
     */
    public function it_uses_null_defaults_when_the_context_has_no_dimensions(): void
    {
        $item = new FeedItem(new \stdClass(), new FeedContext());

        $variables = ScriptingVariables::for($item, 42);

        self::assertSame(42, $variables['value']);
        self::assertNull($variables['channel']);
        self::assertNull($variables['locale']);
        self::assertNull($variables['currency']);
        self::assertSame([], $variables['fields']);
    }
}
