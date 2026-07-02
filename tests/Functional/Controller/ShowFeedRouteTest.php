<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Exercises the public feed route end-to-end (routes.yaml + controller wiring + real storage),
 * which the unit test of the controller cannot verify.
 */
final class ShowFeedRouteTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        $filesystem = self::getContainer()->get('setono_sylius_feed.storage.feed');
        if ($filesystem instanceof FilesystemOperator) {
            $filesystem->deleteDirectory('google');
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_serves_a_generated_feed_file(): void
    {
        self::bootKernel();
        $this->persistEnabledFeed();
        $this->writeCanonicalFile('google/web_en_us_usd.xml', '<rss version="2.0"></rss>');

        $response = self::$kernel->handle(Request::create('/feed/google/web_en_us_usd.xml'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/xml', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        self::assertSame('<rss version="2.0"></rss>', ob_get_clean());
    }

    /**
     * @test
     */
    public function it_returns_404_for_a_missing_file(): void
    {
        self::bootKernel();
        $this->persistEnabledFeed();

        $response = self::$kernel->handle(Request::create('/feed/google/does-not-exist.xml'));

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_404_for_an_unknown_feed(): void
    {
        self::bootKernel();

        $response = self::$kernel->handle(Request::create('/feed/unknown/web.xml'));

        self::assertSame(404, $response->getStatusCode());
    }

    private function persistEnabledFeed(): void
    {
        $feed = new Feed();
        $feed->setCode('google');
        $feed->setEnabled(true);
        $feed->setFormat('google_rss');
        $feed->setCurrentLocale('en_US');
        $feed->setName('Google feed');
        $feed->setSlug('google-feed');

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        $manager->persist($feed);
        $manager->flush();
    }

    private function writeCanonicalFile(string $path, string $contents): void
    {
        $filesystem = self::getContainer()->get('setono_sylius_feed.storage.feed');
        self::assertInstanceOf(FilesystemOperator::class, $filesystem);
        $filesystem->write($path, $contents);
    }
}
