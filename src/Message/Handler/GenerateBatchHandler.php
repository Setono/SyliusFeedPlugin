<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\StringsException;
use function Safe\fclose;
use function Safe\fopen;
use function Safe\fwrite;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Event\BatchGeneratedEvent;
use Setono\SyliusFeedPlugin\Factory\ViolationFactoryInterface;
use Setono\SyliusFeedPlugin\Message\Command\GenerateBatch;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;
use Throwable;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class GenerateBatchHandler implements MessageHandlerInterface
{
    /** @var FeedRepositoryInterface */
    private $feedRepository;

    /** @var ObjectManager */
    private $feedManager;

    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    /** @var Environment */
    private $twig;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Registry */
    private $workflowRegistry;

    /** @var ValidatorInterface */
    private $validator;

    /** @var ViolationFactoryInterface */
    private $violationFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ObjectManager $feedManager,
        FeedTypeRegistryInterface $feedTypeRegistry,
        Environment $twig,
        FilesystemInterface $filesystem,
        EventDispatcherInterface $eventDispatcher,
        Registry $workflowRegistry,
        ValidatorInterface $validator,
        ViolationFactoryInterface $violationFactory,
        LoggerInterface $logger
    ) {
        $this->feedRepository = $feedRepository;
        $this->feedManager = $feedManager;
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->twig = $twig;
        $this->filesystem = $filesystem;
        $this->eventDispatcher = $eventDispatcher;
        $this->workflowRegistry = $workflowRegistry;
        $this->validator = $validator;
        $this->violationFactory = $violationFactory;
        $this->logger = $logger;
    }

    /**
     * @throws Throwable
     */
    public function __invoke(GenerateBatch $message): void
    {
        $feed = $this->getFeed($message->getFeedId());

        $workflow = $this->getWorkflow($feed);

        try {
            $feedType = $this->feedTypeRegistry->get($feed->getFeedType());

            $items = $feedType->getDataProvider()->getItems($message->getBatch());

            $normalizer = $feedType->getNormalizer();

            $template = $this->twig->load($feedType->getTemplate());

            /** @var ChannelInterface $channel */
            foreach ($feed->getChannels() as $channel) {
                foreach ($channel->getLocales() as $locale) {
                    $stream = $this->openStream();

                    foreach ($items as $item) {
                        $arr = $normalizer->normalize($item, $channel, $locale);
                        foreach ($arr as $val) {
                            // todo fire event here so the user can hook into this event and change properties

                            $constraintViolationList = $this->validator->validate($val, null, ['setono_sylius_feed']); // todo should be a parameter
                            if ($constraintViolationList->count() > 0) {
                                foreach ($constraintViolationList as $constraintViolation) {
                                    $violation = $this->violationFactory->createFromConstraintViolation(
                                        $constraintViolation, $channel, $locale
                                    );

                                    $feed->addViolation($violation);
                                }
                            }
                            fwrite($stream, $template->renderBlock('item', ['item' => $val]));
                        }
                    }

                    $path = $this->getPath($feed, $channel->getCode(), $locale->getCode());
                    $res = $this->filesystem->writeStream($path, $stream);

                    $this->closeStream($stream);

                    Assert::true($res, 'An error occurred when trying to write a feed item');
                }
            }

            $this->eventDispatcher->dispatch(new BatchGeneratedEvent($feed));
        } catch (Throwable $e) {
            $this->logger->critical($e->getMessage(), ['feedId' => $feed->getId()]);

            $this->applyErrorTransition($workflow, $feed);

            $this->feedManager->flush();

            throw $e;
        }
    }

    private function getFeed(int $feedId): FeedInterface
    {
        /** @var FeedInterface|null $feed */
        $feed = $this->feedRepository->find($feedId);

        if (null === $feed) {
            throw new UnrecoverableMessageHandlingException('Feed does not exist');
        }

        return $feed;
    }

    private function getWorkflow(FeedInterface $feed): Workflow
    {
        try {
            $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        } catch (InvalidArgumentException $e) {
            throw new UnrecoverableMessageHandlingException('An error occurred when trying to get the workflow for the feed',
                0, $e);
        }

        return $workflow;
    }

    /**
     * @throws StringsException
     */
    private function getPath(FeedInterface $feed, string $channel, string $locale): string
    {
        $dir = sprintf('%s/%s/%s', $feed->getUuid(), $channel, $locale);

        do {
            $path = $dir . '/' . uniqid('partial-', true);
        } while ($this->filesystem->has($path));

        return $path;
    }

    /**
     * @return resource
     *
     * @throws FilesystemException
     */
    private function openStream()
    {
        // needs to be w+ since we use the same stream later to read from
        return fopen('php://temp', 'w+b');
    }

    /**
     * @param resource $stream
     */
    private function closeStream($stream): void
    {
        try {
            // tries to close the stream although it may already have been closed by flysystem
            fclose($stream);
        } catch (FilesystemException $e) {
        }
    }

    /**
     * @throws StringsException
     */
    private function applyErrorTransition(Workflow $workflow, FeedInterface $feed): void
    {
        if (!$workflow->can($feed, FeedGraph::TRANSITION_ERRORED)) {
            throw new InvalidArgumentException(sprintf('The transition "%s" could not be applied. State was: "%s"',
                FeedGraph::TRANSITION_ERRORED, $feed->getState()));
        }

        $workflow->apply($feed, FeedGraph::TRANSITION_ERRORED);
    }
}
