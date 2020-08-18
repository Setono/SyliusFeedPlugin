<?php

declare(strict_types=1);

namespace spec\Setono\SyliusFeedPlugin\Registry;

use InvalidArgumentException;
use PhpSpec\ObjectBehavior;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistry;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;

class FeedTypeRegistrySpec extends ObjectBehavior
{
    public function let(FeedTypeInterface $feedType1, FeedTypeInterface $feedType2): void
    {
        $feedType1->getCode()->willReturn('feed_type_1');
        $feedType2->getCode()->willReturn('feed_type_2');

        $this->beConstructedWith($feedType1, $feedType2);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(FeedTypeRegistry::class);
    }

    public function it_implements_feed_type_registry_interface(): void
    {
        $this->shouldImplement(FeedTypeRegistryInterface::class);
    }

    public function it_has_feed_type_1(): void
    {
        $this->has('feed_type_1')->shouldReturn(true);
    }

    public function it_gets_feed_type_1(FeedTypeInterface $feedType1): void
    {
        $this->get('feed_type_1')->shouldReturn($feedType1);
    }

    public function it_throws_exception_when_feed_type_does_not_exist(): void
    {
        $this->shouldThrow(InvalidArgumentException::class)->during('get', ['non_existent_code']);
    }

    public function it_returns_all_feed_types(FeedTypeInterface $feedType1, FeedTypeInterface $feedType2): void
    {
        $this->all()->shouldReturn([
            'feed_type_1' => $feedType1,
            'feed_type_2' => $feedType2,
        ]);
    }
}
