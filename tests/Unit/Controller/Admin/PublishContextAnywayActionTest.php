<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Controller\Admin\PublishContextAnywayAction;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Repository\FeedContextResultRepositoryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

final class PublishContextAnywayActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_promotes_the_retained_candidate_and_marks_it_published(): void
    {
        $stream = fopen('php://temp', 'r');
        self::assertIsResource($stream);

        $feed = new Feed();
        $feed->setCode('google');

        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->find(1)->willReturn($feed);

        $listing = (static function (): \Generator {
            yield new DirectoryAttributes('google/nested'); // not a file -> skipped
            yield new FileAttributes('google/web_en_us_usd.xml'); // matches the requested context
            yield new FileAttributes('google/web_da_dk_dkk.xml'); // a different context -> untouched
        })();

        $temporary = $this->prophesize(FilesystemOperator::class);
        $temporary->listContents('google', true)->willReturn(new DirectoryListing($listing));
        $temporary->readStream('google/web_en_us_usd.xml')->willReturn($stream);
        $temporary->delete('google/web_en_us_usd.xml')->shouldBeCalledOnce();
        $temporary->delete('google/web_da_dk_dkk.xml')->shouldNotBeCalled();

        $canonical = $this->prophesize(FilesystemOperator::class);
        $canonical->writeStream('google/web_en_us_usd.xml', $stream)->shouldBeCalledOnce();
        $canonical->writeStream('google/web_da_dk_dkk.xml', Argument::any())->shouldNotBeCalled();

        $result = new FeedContextResult();
        $result->setPublishState(FeedContextResultInterface::PUBLISH_STATE_BLOCKED);

        $resultRepository = $this->prophesize(FeedContextResultRepositoryInterface::class);
        $resultRepository->findLatestForContext($feed, 'web_en_us_usd')->willReturn($result);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->flush()->shouldBeCalledOnce();

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(FeedContextResult::class)->willReturn($manager->reveal());

        $router = $this->prophesize(RouterInterface::class);
        $router->generate('setono_sylius_feed_admin_feed_results', Argument::any())->willReturn('/admin/feeds/1/results');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $action = new PublishContextAnywayAction(
            $managerRegistry->reveal(),
            $repository->reveal(),
            $resultRepository->reveal(),
            $temporary->reveal(),
            $canonical->reveal(),
            $router->reveal(),
        );

        $response = $action($request, 1, 'web_en_us_usd');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/feeds/1/results', $response->getTargetUrl());
        self::assertTrue($result->isPublished());

        /** @var array<string, list<string>> $flashes */
        $flashes = $session->getFlashBag()->peekAll();
        self::assertContains('setono_sylius_feed.feed.context_published', $flashes['success'] ?? []);

        fclose($stream);
    }

    /**
     * @test
     */
    public function it_404s_for_a_missing_feed(): void
    {
        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->find(999)->willReturn(null);

        $temporary = $this->prophesize(FilesystemOperator::class);
        $temporary->listContents(Argument::cetera())->shouldNotBeCalled();

        $action = new PublishContextAnywayAction(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $repository->reveal(),
            $this->prophesize(FeedContextResultRepositoryInterface::class)->reveal(),
            $temporary->reveal(),
            $this->prophesize(FilesystemOperator::class)->reveal(),
            $this->prophesize(RouterInterface::class)->reveal(),
        );

        $this->expectException(NotFoundHttpException::class);

        $action(new Request(), 999, 'web_en_us_usd');
    }
}
