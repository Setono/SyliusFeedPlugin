<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Resolver;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Resolver\FeedExtensionResolver;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class FeedExtensionResolverTest extends TestCase
{
    /**
     * @test
     */
    public function it_resolves_extension(): void
    {
        $feed = $this->createMock(FeedInterface::class);
        $feed->method('getFeedType')->willReturn('feed_type');

        $feedType = $this->createMock(FeedTypeInterface::class);
        $feedType->method('getTemplate')->willReturn('template.twig.html');

        $feedTypeRegistry = $this->createMock(FeedTypeRegistryInterface::class);
        $feedTypeRegistry->method('get')->with(self::equalTo('feed_type'))->willReturn($feedType);

        $resolver = new FeedExtensionResolver($feedTypeRegistry, $this->getTwig());

        self::assertSame('xml', $resolver->resolve($feed));
    }

    private function getTwig(): Environment
    {
        $loader = new ArrayLoader([
            'template.twig.html' => '{% block extension %}xml{% endblock %}',
        ]);

        return new Environment($loader);
    }
}
