<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\FeedType;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\FeedType\CustomerFeedType;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistryInterface;

final class CustomerFeedTypeTest extends TestCase
{
    use ProphecyTrait;

    private DataSourceInterface $dataSource;

    private CustomerFeedType $feedType;

    protected function setUp(): void
    {
        $this->dataSource = $this->prophesize(DataSourceInterface::class)->reveal();

        $resolver = $this->prophesize(ValueResolverInterface::class);
        $resolver->getLabel()->willReturn('label');
        $resolver->getType()->willReturn(FieldType::STRING);

        $registry = $this->prophesize(ValueResolverRegistryInterface::class);
        $registry->has(\Prophecy\Argument::type('string'))->willReturn(true);
        $registry->get(\Prophecy\Argument::type('string'))->willReturn($resolver->reveal());

        $this->feedType = new CustomerFeedType($this->dataSource, $registry->reveal());
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('customer', $this->feedType->getCode());
        self::assertSame('setono_sylius_feed.feed_type.customer', $this->feedType->getLabel());
        self::assertSame($this->dataSource, $this->feedType->getDataSource());
        self::assertSame([], $this->feedType->getScopeDimensions());
    }

    /**
     * @test
     */
    public function it_exposes_its_available_source_fields(): void
    {
        $fields = $this->feedType->getAvailableFields();

        foreach (['email', 'first_name', 'last_name', 'group', 'subscribed_to_newsletter', 'created_at'] as $name) {
            self::assertArrayHasKey($name, $fields);
            self::assertSame($name, $fields[$name]->getName());
        }
    }

    /**
     * @test
     */
    public function it_omits_fields_with_no_registered_resolver(): void
    {
        $registry = $this->prophesize(ValueResolverRegistryInterface::class);
        $registry->has(\Prophecy\Argument::type('string'))->willReturn(false);

        $feedType = new CustomerFeedType($this->dataSource, $registry->reveal());

        self::assertSame([], $feedType->getAvailableFields());
    }
}
