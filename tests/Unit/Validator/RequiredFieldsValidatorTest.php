<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Validator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidator;

/**
 * @covers \Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidator
 */
final class RequiredFieldsValidatorTest extends TestCase
{
    private RequiredFieldsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RequiredFieldsValidator();
    }

    /**
     * @test
     */
    public function it_reports_absent_and_empty_required_fields(): void
    {
        $item = new FeedItem(new \stdClass(), new FeedContext());
        $item->set('g:id', 'SKU-1');
        $item->set('g:title', '');
        $item->set('g:images', []);

        $missing = $this->validator->findMissingFields($item, ['g:id', 'g:title', 'g:images', 'g:link']);

        self::assertSame(['g:title', 'g:images', 'g:link'], $missing);
    }

    /**
     * @test
     */
    public function it_reports_nothing_when_all_required_fields_are_present(): void
    {
        $item = new FeedItem(new \stdClass(), new FeedContext());
        $item->set('g:id', 'SKU-1');
        $item->set('g:title', 'Acme');

        self::assertSame([], $this->validator->findMissingFields($item, ['g:id', 'g:title']));
    }
}
