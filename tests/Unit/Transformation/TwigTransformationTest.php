<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Scripting\TwigTemplateRendererInterface;
use Setono\SyliusFeedPlugin\Transformation\TwigTransformation;

final class TwigTransformationTest extends TestCase
{
    use ProphecyTrait;

    private FeedItem $item;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
    }

    /**
     * @test
     */
    public function it_has_the_twig_type(): void
    {
        $renderer = $this->prophesize(TwigTemplateRendererInterface::class)->reveal();
        $transformation = new TwigTransformation($renderer);

        self::assertSame('twig', $transformation->getType());

        $config = TwigTransformation::of('{{ value|upper }}');

        self::assertSame('twig', $config->getType());
        self::assertSame(['template' => '{{ value|upper }}'], $config->getParams());
    }

    /**
     * @test
     */
    public function it_renders_the_template_once_for_a_scalar_value(): void
    {
        $renderer = $this->prophesize(TwigTemplateRendererInterface::class);
        $renderer->render('{{ value|upper }}', Argument::type('array'))
            ->shouldBeCalledTimes(1)
            ->willReturn('HI');

        $transformation = new TwigTransformation($renderer->reveal());

        self::assertSame('HI', $transformation->apply('hi', ['template' => '{{ value|upper }}'], $this->item));
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        $renderer = $this->prophesize(TwigTemplateRendererInterface::class);
        $renderer->render('{{ value|upper }}', Argument::that(
            static fn (array $variables): bool => 'a' === $variables['value'],
        ))->willReturn('A');
        $renderer->render('{{ value|upper }}', Argument::that(
            static fn (array $variables): bool => 'b' === $variables['value'],
        ))->willReturn('B');

        $transformation = new TwigTransformation($renderer->reveal());

        $result = $transformation->apply(['a', 'b'], ['template' => '{{ value|upper }}'], $this->item);

        self::assertSame(['A', 'B'], $result);
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_the_template_is_missing(): void
    {
        $renderer = $this->prophesize(TwigTemplateRendererInterface::class);
        $renderer->render(Argument::cetera())->shouldNotBeCalled();

        $transformation = new TwigTransformation($renderer->reveal());

        self::assertSame('hi', $transformation->apply('hi', [], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_the_template_is_empty(): void
    {
        $renderer = $this->prophesize(TwigTemplateRendererInterface::class);
        $renderer->render(Argument::cetera())->shouldNotBeCalled();

        $transformation = new TwigTransformation($renderer->reveal());

        self::assertSame('hi', $transformation->apply('hi', ['template' => ''], $this->item));
    }

    /**
     * @test
     */
    public function it_returns_the_element_unchanged_when_rendering_throws(): void
    {
        $renderer = $this->prophesize(TwigTemplateRendererInterface::class);
        $renderer->render(Argument::cetera())->willThrow(new \RuntimeException('boom'));

        $transformation = new TwigTransformation($renderer->reveal());

        self::assertSame('hi', $transformation->apply('hi', ['template' => '{{ broken'], $this->item));
    }

    /**
     * @test
     */
    public function it_returns_each_element_unchanged_on_a_per_element_rendering_error(): void
    {
        $renderer = $this->prophesize(TwigTemplateRendererInterface::class);
        $renderer->render('{{ value }}', Argument::that(
            static fn (array $variables): bool => 'a' === $variables['value'],
        ))->willReturn('A');
        $renderer->render('{{ value }}', Argument::that(
            static fn (array $variables): bool => 'b' === $variables['value'],
        ))->willThrow(new \RuntimeException('boom'));

        $transformation = new TwigTransformation($renderer->reveal());

        $result = $transformation->apply(['a', 'b'], ['template' => '{{ value }}'], $this->item);

        self::assertSame(['A', 'b'], $result);
    }
}
