<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use InvalidArgumentException;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
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
use Setono\SyliusFeedPlugin\Event\GenerateBatchItemEvent;
use Setono\SyliusFeedPlugin\Event\GenerateBatchViolationEvent;
use Setono\SyliusFeedPlugin\Exception\GenerateBatchException;
use Setono\SyliusFeedPlugin\Factory\ViolationFactoryInterface;
use Setono\SyliusFeedPlugin\Generator\FeedPathGeneratorInterface;
use Setono\SyliusFeedPlugin\Generator\TemporaryFeedPathGenerator;
use Setono\SyliusFeedPlugin\Message\Command\GenerateBatch;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;
use Throwable;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class GenerateBatchHandler implements MessageHandlerInterface
{
    use GetChannelTrait;
    use GetFeedTrait;
    use GetLocaleTrait;

    /** @var ObjectManager */
    private $feedManager;

    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    /** @var Environment */
    private $twig;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var FeedPathGeneratorInterface */
    private $temporaryFeedPathGenerator;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Registry */
    private $workflowRegistry;

    /** @var ValidatorInterface */
    private $validator;

    /** @var ViolationFactoryInterface */
    private $violationFactory;

    /** @var SerializerInterface */
    private $serializer;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ChannelRepositoryInterface $channelRepository,
        RepositoryInterface $localeRepository,
        ObjectManager $feedManager,
        FeedTypeRegistryInterface $feedTypeRegistry,
        Environment $twig,
        FilesystemInterface $filesystem,
        FeedPathGeneratorInterface $temporaryFeedPathGenerator,
        EventDispatcherInterface $eventDispatcher,
        Registry $workflowRegistry,
        ValidatorInterface $validator,
        ViolationFactoryInterface $violationFactory,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->feedRepository = $feedRepository;
        $this->channelRepository = $channelRepository;
        $this->localeRepository = $localeRepository;
        $this->feedManager = $feedManager;
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->twig = $twig;
        $this->filesystem = $filesystem;
        $this->temporaryFeedPathGenerator = $temporaryFeedPathGenerator;
        $this->eventDispatcher = $eventDispatcher;
        $this->workflowRegistry = $workflowRegistry;
        $this->validator = $validator;
        $this->violationFactory = $violationFactory;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @throws Throwable
     */
    public function __invoke(GenerateBatch $message): void
    {
        $feed = $this->getFeed($message->getFeedId());

        if ($feed->isErrored()) {
            return;
        }

        $channel = $this->getChannel($message->getChannelId());
        $locale = $this->getLocale($message->getLocaleId());

        $workflow = $this->getWorkflow($feed);

        try {
            $feedType = $this->feedTypeRegistry->get($feed->getFeedType());

            $items = $feedType->getDataProvider()->getItems($message->getBatch());

            $itemContext = $feedType->getItemContext();

            $template = $this->twig->load($feedType->getTemplate());

            $stream = $this->openStream();

            foreach ($items as $item) {
                try {
                    $contextList = $itemContext->getContextList($item, $channel, $locale);
                    foreach ($contextList as $context) {
                        $this->eventDispatcher->dispatch(new GenerateBatchItemEvent(
                            $feed, $feedType, $channel, $locale, $context
                        ));

                        $constraintViolationList = $this->validator->validate(
                            $context, null, $feedType->getValidationGroups()
                        );

                        $hasErrorViolation = false;

                        if ($constraintViolationList->count() > 0) {
                            foreach ($constraintViolationList as $constraintViolation) {
                                $violation = $this->violationFactory->createFromConstraintViolation(
                                    $constraintViolation, $channel, $locale, $this->serializer->serialize($context, 'json', [
                                        JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
                                    ])
                                );

                                if ($violation->getSeverity() === 'error') {
                                    $hasErrorViolation = true;
                                }

                                $feed->addViolation($violation);
                            }

                            $this->eventDispatcher->dispatch(new GenerateBatchViolationEvent(
                                $feed, $feedType, $channel, $locale, $context, $constraintViolationList
                            ));
                        }

                        // do not write the item to the stream if a violation has severity error
                        if ($hasErrorViolation) {
                            continue;
                        }

                        fwrite($stream, $template->renderBlock('item', ['item' => $context]));
                    }
                } catch (Throwable $e) {
                    $newException = new GenerateBatchException($e->getMessage(), $e);
                    $newException->setResourceId($item->getId());

                    throw $newException;
                }
            }

            $dir = $this->temporaryFeedPathGenerator->generate($feed, $channel->getCode(), $locale->getCode());
            $path = TemporaryFeedPathGenerator::getPartialFile($dir, $this->filesystem);

            $res = $this->filesystem->writeStream((string) $path, $stream);

            $this->closeStream($stream);

            Assert::true($res, 'An error occurred when trying to write a feed item');

            $this->feedManager->flush();
            $this->feedManager->clear();

            $this->eventDispatcher->dispatch(new BatchGeneratedEvent($feed));
        } catch (GenerateBatchException $e) {
            $e->setFeedId($feed->getId());
            $e->setChannelCode($channel->getCode());
            $e->setLocaleCode($locale->getCode());

            $this->logger->critical($e->getMessage(), [
                'resourceId' => $e->getResourceId(),
                'feedId' => $feed->getId(),
                'channelCode' => $channel->getCode(),
                'localeCode' => $locale->getCode(),
            ]);

            $this->applyErrorTransition($workflow, $feed);

            $this->feedManager->flush();

            throw $e;
        } catch (Throwable $e) {
            $this->logger->critical($e->getMessage(), [
                'feedId' => $feed->getId(),
                'channelCode' => $channel->getCode(),
                'localeCode' => $locale->getCode(),
            ]);

            $this->applyErrorTransition($workflow, $feed);

            $this->feedManager->flush();

            $newException = new GenerateBatchException($e->getMessage(), $e);
            $newException->setFeedId($feed->getId());
            $newException->setChannelCode($channel->getCode());
            $newException->setLocaleCode($locale->getCode());

            throw $newException;
        }
    }

    private function getWorkflow(FeedInterface $feed): Workflow
    {
        try {
            $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        } catch (InvalidArgumentException $e) {
            throw new UnrecoverableMessageHandlingException(
                'An error occurred when trying to get the workflow for the feed', 0, $e
            );
        }

        return $workflow;
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
        // if the feed is already errored we won't want to throw an exception
        if ($feed->isErrored()) {
            return;
        }

        if (!$workflow->can($feed, FeedGraph::TRANSITION_ERRORED)) {
            throw new InvalidArgumentException(sprintf(
                'The transition "%s" could not be applied. State was: "%s"',
                FeedGraph::TRANSITION_ERRORED, $feed->getState()
            ));
        }

        $workflow->apply($feed, FeedGraph::TRANSITION_ERRORED);
    }
}
