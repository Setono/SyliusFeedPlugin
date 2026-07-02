<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Delivery;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Delivery\DeliveryMatcher;
use Setono\SyliusFeedPlugin\Delivery\DeliveryService;
use Setono\SyliusFeedPlugin\Delivery\DeliveryTransportRegistry;
use Setono\SyliusFeedPlugin\Delivery\LocalDeliveryTransport;
use Setono\SyliusFeedPlugin\Delivery\PathTemplateRenderer;
use Setono\SyliusFeedPlugin\Model\DeliveryTarget;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

final class DeliveryServiceTest extends TestCase
{
    use ProphecyTrait;

    private string $canonicalDir;

    private string $targetDir;

    private FilesystemOperator $canonical;

    private FilesystemOperator $target;

    protected function setUp(): void
    {
        $this->canonicalDir = sys_get_temp_dir() . '/ssfp-delivery-canonical-' . bin2hex(random_bytes(6));
        $this->targetDir = sys_get_temp_dir() . '/ssfp-delivery-target-' . bin2hex(random_bytes(6));

        $this->canonical = new Filesystem(new LocalFilesystemAdapter($this->canonicalDir));
        $this->target = new Filesystem(new LocalFilesystemAdapter($this->targetDir));
    }

    protected function tearDown(): void
    {
        (new SymfonyFilesystem())->remove([$this->canonicalDir, $this->targetDir]);
    }

    /**
     * @test
     */
    public function it_delivers_the_whole_file_set_to_a_matching_local_target(): void
    {
        $paths = [
            'google/web_en_us_usd.xml' => 'MANIFEST',
            'google/web_en_us_usd-1.xml' => 'PART-ONE',
            'google/web_en_us_usd-2.xml' => 'PART-TWO',
        ];
        foreach ($paths as $path => $contents) {
            $this->canonical->write($path, $contents);
        }

        $result = $this->result(array_keys($paths));
        $feed = $this->feed($this->target('local', ['path' => $this->targetDir], 'out/{contextKey}{part}.{ext}'));

        $this->service()->deliver($feed, $result, $result->getPaths());

        $deliveries = $result->getDeliveries();
        self::assertCount(3, $deliveries);
        foreach ($deliveries as $delivery) {
            self::assertSame('delivered', $delivery['status']);
            self::assertTrue($this->target->fileExists($delivery['path']));
        }

        self::assertSame('MANIFEST', $this->target->read('out/web_en_us_usd.xml'));
        self::assertSame('PART-ONE', $this->target->read('out/web_en_us_usd1.xml'));
        self::assertSame('PART-TWO', $this->target->read('out/web_en_us_usd2.xml'));
    }

    /**
     * @test
     */
    public function it_skips_a_non_matching_target(): void
    {
        $this->canonical->write('google/web_en_us_usd.xml', 'MANIFEST');

        $result = $this->result(['google/web_en_us_usd.xml']);
        $target = $this->target('local', ['path' => $this->targetDir], 'out/{contextKey}.{ext}');
        $target->setMatch(['channel' => 'mobile']);
        $feed = $this->feed($target);

        $this->service()->deliver($feed, $result, $result->getPaths());

        self::assertSame([], $result->getDeliveries());
        self::assertFalse($this->target->fileExists('out/web_en_us_usd.xml'));
    }

    /**
     * @test
     */
    public function it_records_an_error_and_does_not_throw_when_a_target_fails(): void
    {
        $this->canonical->write('google/web_en_us_usd.xml', 'MANIFEST');

        $result = $this->result(['google/web_en_us_usd.xml']);
        // An unknown transport type cannot be resolved -> recorded as a target-level error.
        $feed = $this->feed($this->target('does-not-exist', [], 'out/{contextKey}.{ext}'));

        $this->service()->deliver($feed, $result, $result->getPaths());

        $deliveries = $result->getDeliveries();
        self::assertCount(1, $deliveries);
        self::assertSame('error', $deliveries[0]['status']);
        self::assertArrayHasKey('error', $deliveries[0]);
    }

    /**
     * @test
     */
    public function it_isolates_targets_so_one_failure_does_not_starve_the_others(): void
    {
        $this->canonical->write('google/web_en_us_usd.xml', 'MANIFEST');

        $result = $this->result(['google/web_en_us_usd.xml']);
        $feed = $this->feed(
            $this->target('does-not-exist', [], 'out/{contextKey}.{ext}'),
            $this->target('local', ['path' => $this->targetDir], 'ok/{contextKey}.{ext}'),
        );

        $this->service()->deliver($feed, $result, $result->getPaths());

        $statuses = array_map(static fn (array $delivery): string => $delivery['status'], $result->getDeliveries());
        self::assertContains('error', $statuses);
        self::assertContains('delivered', $statuses);
        self::assertSame('MANIFEST', $this->target->read('ok/web_en_us_usd.xml'));
    }

    private function service(): DeliveryService
    {
        $manager = $this->prophesize(EntityManagerInterface::class);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Argument::any())->willReturn($manager->reveal());

        return new DeliveryService(
            $managerRegistry->reveal(),
            new DeliveryTransportRegistry([new LocalDeliveryTransport()]),
            new DeliveryMatcher(),
            new PathTemplateRenderer(),
            $this->canonical,
        );
    }

    /**
     * @param list<string> $paths
     */
    private function result(array $paths): FeedContextResult
    {
        $result = new FeedContextResult();
        $result->setContextKey('web_en_us_usd');
        $result->setChannelCode('web');
        $result->setLocaleCode('en_US');
        $result->setCurrencyCode('USD');
        $result->setPaths($paths);

        return $result;
    }

    /**
     * @param array<string, mixed> $transportConfig
     */
    private function target(string $transport, array $transportConfig, string $pathTemplate): DeliveryTarget
    {
        $target = new DeliveryTarget();
        $target->setTransport($transport);
        $target->setTransportConfig($transportConfig);
        $target->setPathTemplate($pathTemplate);

        return $target;
    }

    private function feed(DeliveryTarget ...$targets): Feed
    {
        $feed = new Feed();
        $feed->setCode('google');
        foreach ($targets as $target) {
            $feed->addDeliveryTarget($target);
        }

        return $feed;
    }
}
