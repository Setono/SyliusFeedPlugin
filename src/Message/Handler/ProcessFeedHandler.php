<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use InvalidArgumentException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedChunk;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
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

    /** @var Environment */
    private $twig;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ObjectManager $feedManager,
        FeedTypeRegistryInterface $feedTypeRegistry,
        MessageBusInterface $commandBus,
        Registry $workflowRegistry,
        Environment $twig
    ) {
        $this->feedRepository = $feedRepository;
        $this->feedManager = $feedManager;
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->commandBus = $commandBus;
        $this->workflowRegistry = $workflowRegistry;
        $this->twig = $twig;
    }

    /**
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
            throw new InvalidArgumentException('Feed does not exist');
        }

        $feedType = $this->feedTypeRegistry->get($feed->getFeedType());

        $this->validateTemplate($feedType->getTemplate());

        $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        if (!$workflow->can($feed, FeedGraph::TRANSITION_PROCESS)) {
            throw new InvalidArgumentException('The feed is not in a valid state. It could be processing already?');
        }

        $workflow->apply($feed, FeedGraph::TRANSITION_PROCESS);

        $this->feedManager->flush();

        $dataProvider = $feedType->getDataProvider();
        foreach ($dataProvider->getBatches() as $batch) {
            $this->commandBus->dispatch(new GenerateFeedChunk($feed->getId(), $batch));
        }
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
}
