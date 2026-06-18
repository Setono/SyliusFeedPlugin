<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Model\FeedTranslation;

/**
 * @covers \Setono\SyliusFeedPlugin\Model\FeedTranslation
 */
final class FeedTranslationTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_name_and_a_slug(): void
    {
        $translation = new FeedTranslation();

        self::assertNull($translation->getId());
        self::assertNull($translation->getName());
        self::assertNull($translation->getSlug());

        $translation->setName('Google Shopping');
        $translation->setSlug('google-shopping');

        self::assertSame('Google Shopping', $translation->getName());
        self::assertSame('google-shopping', $translation->getSlug());
    }
}
