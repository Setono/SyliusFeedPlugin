<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use InvalidArgumentException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedChunk;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class ProcessFeedHandler implements MessageHandlerInterface
{
    /** @var FeedRepositoryInterface */
    private $feedRepository;

    /** @var ObjectManager */
    private $feedManager;

    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    /** @var MessageBusInterface */
    private $commandBus;

    /** @var Registry */
    private $workflowRegistry;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var Environment */
    private $twig;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ObjectManager $feedManager,
        FeedTypeRegistryInterface $feedTypeRegistry,
        MessageBusInterface $commandBus,
        Registry $workflowRegistry,
        FilesystemInterface $filesystem,
        Environment $twig
    ) {
        $this->feedRepository = $feedRepository;
        $this->feedManager = $feedManager;
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->commandBus = $commandBus;
        $this->workflowRegistry = $workflowRegistry;
        $this->filesystem = $filesystem;
        $this->twig = $twig;
    }

    /**
     * @throws FileExistsException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws StringsException
     * @throws SyntaxError
     */
    public function __invoke(ProcessFeed $message): void
    {
        /** @var FeedInterface|null $feed */
        $feed = $this->feedRepository->find($message->getFeedId());

        if (null === $feed) {
            throw new InvalidArgumentException('Feed does not exist'); // todo better exception
        }

        $feedType = $this->feedTypeRegistry->get($feed->getFeedType());

        $this->validateTemplate($feedType->getTemplate());

        $workflow = $this->workflowRegistry->get($feed);
        if (!$workflow->can($feed, FeedGraph::TRANSITION_PROCESS)) {
            throw new InvalidArgumentException('The feed is not in a valid state. It could be processing already?'); // todo better exception
        }

        $workflow->apply($feed, FeedGraph::TRANSITION_PROCESS);

        $this->feedManager->flush();

        $this->createBaseFiles($feed, $feedType);

        $dataProvider = $feedType->getDataProvider();

        $batchCount = 0; // todo it would be better to have this number before hand, maybe it should go into the data provider?
        foreach ($dataProvider->getBatches() as $batch) {
            $this->commandBus->dispatch(new GenerateFeedChunk($feed->getId(), $batch));

            ++$batchCount;
        }

        $feed->setBatches($batchCount);

        $this->feedManager->flush();
    }

    /**
     * @throws StringsException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function validateTemplate(string $template): void
    {
        $templateWrapper = $this->twig->load($template);
        $requiredBlocks = ['extension', 'item'];

        foreach ($requiredBlocks as $requiredBlock) {
            if (!$templateWrapper->hasBlock($requiredBlock)) {
                throw new InvalidArgumentException(sprintf('The template "%s" does not have the block "%s" defined.', $template, $requiredBlock));
            }
        }
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws StringsException
     * @throws SyntaxError
     * @throws FileExistsException
     */
    private function createBaseFiles(FeedInterface $feed, FeedTypeInterface $feedType): void
    {
        $template = $this->twig->load('@SetonoSyliusFeedPlugin/Template/feed.txt.twig');

        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $path = sprintf('%s/%s/%s/%s', $feed->getUuid(), $channel->getCode(), $locale->getCode(), '_feed');

                $content = $template->render(array_merge($feedType->getFeedContext()->getContext($feed, $channel->getCode(), $locale->getCode()), ['feed' => $feedType->getTemplate()]));

                try {
                    $this->filesystem->delete($path);
                } catch (FileNotFoundException $e) {
                }

                $this->filesystem->write($path, $content);
            }
        }
    }
}
