<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use const JSON_INVALID_UTF8_IGNORE;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FilesystemOperator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Setono\SyliusFeedPlugin\Event\BatchGeneratedEvent;
use Setono\SyliusFeedPlugin\Event\GenerateBatchItemEvent;
use Setono\SyliusFeedPlugin\Event\GenerateBatchViolationEvent;
use Setono\SyliusFeedPlugin\Exception\GenerateBatchException;
use Setono\SyliusFeedPlugin\Factory\ViolationFactoryInterface;
use Setono\SyliusFeedPlugin\Generator\FeedPathGeneratorInterface;
use Setono\SyliusFeedPlugin\Generator\TemporaryFeedPathGenerator;
use Setono\SyliusFeedPlugin\Message\Command\GenerateBatch;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\ViolationInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;
use Throwable;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class GenerateBatchHandler implements MessageHandlerInterface
{
    use GetChannelTrait;
    use GetFeedTrait;
    use GetLocaleTrait;

    private RequestContext $initialRequestContext;

    private FilesystemInterface|FilesystemOperator $filesystem;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ChannelRepositoryInterface $channelRepository,
        RepositoryInterface $localeRepository,
        private readonly ObjectManager $feedManager,
        private readonly FeedTypeRegistryInterface $feedTypeRegistry,
        private readonly Environment $twig,
        $filesystem,
        private readonly FeedPathGeneratorInterface $temporaryFeedPathGenerator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Registry $workflowRegistry,
        private readonly ValidatorInterface $validator,
        private readonly ViolationFactoryInterface $violationFactory,
        private readonly SerializerInterface $serializer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
        $this->feedRepository = $feedRepository;
        $this->channelRepository = $channelRepository;
        $this->localeRepository = $localeRepository;
        if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
            $this->filesystem = $filesystem;
        } elseif ($filesystem instanceof FilesystemOperator) {
            $this->filesystem = $filesystem;
        } else {
            throw new InvalidArgumentException(sprintf(
                'The filesystem must be an instance of %s or %s',
                FilesystemInterface::class,
                FilesystemOperator::class,
            ));
        }
    }

    public function __invoke(GenerateBatch $message): void
    {
        $feed = $this->getFeed($message->getFeedId());

        if ($feed->isErrored()) {
            return;
        }

        $channel = $this->getChannel($message->getChannelId());
        $locale = $this->getLocale($message->getLocaleId());

        $this->setTemporaryRequestContext($channel);

        $workflow = $this->getWorkflow($feed);

        try {
            $feedType = $this->feedTypeRegistry->get((string) $feed->getFeedType());

            $items = $feedType->getDataProvider()->getItems($message->getBatch());

            $itemContext = $feedType->getItemContext();

            $template = $this->twig->load($feedType->getTemplate());

            $stream = $this->openStream();

            foreach ($items as $item) {
                try {
                    /** @phpstan-ignore arguments.count */
                    $contextList = $itemContext->getContextList($item, $channel, $locale, $feed);

                    /** @var array|object $context */
                    foreach ($contextList as $context) {
                        $this->eventDispatcher->dispatch(new GenerateBatchItemEvent(
                            $feed,
                            $feedType,
                            $channel,
                            $locale,
                            $context,
                            $item,
                        ));

                        $constraintViolationList = $this->validator->validate(
                            $context,
                            null,
                            $feedType->getValidationGroups(),
                        );

                        $hasErrorViolation = false;

                        if ($constraintViolationList->count() > 0) {
                            /** @var ConstraintViolationInterface $constraintViolation */
                            foreach ($constraintViolationList as $constraintViolation) {
                                $violation = $this->violationFactory->createFromConstraintViolation(
                                    $constraintViolation,
                                    $channel,
                                    $locale,
                                    $this->serializer->serialize($context, 'json', [
                                        JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_IGNORE,
                                        'setono_sylius_feed_data' => true,
                                    ]),
                                );

                                if ($violation->getSeverity() === ViolationInterface::SEVERITY_ERROR) {
                                    $hasErrorViolation = true;
                                }

                                $feed->addViolation($violation);
                            }

                            $this->eventDispatcher->dispatch(new GenerateBatchViolationEvent(
                                $feed,
                                $feedType,
                                $channel,
                                $locale,
                                $context,
                                $constraintViolationList,
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

            $dir = $this->temporaryFeedPathGenerator->generate($feed, (string) $channel->getCode(), (string) $locale->getCode());
            $filesystem = $this->filesystem;
            $path = TemporaryFeedPathGenerator::getPartialFile($dir, $filesystem);

            if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
                $res = $filesystem->writeStream((string) $path, $stream);
                fclose($stream);

                Assert::true($res, 'An error occurred when trying to write a feed item');
            } else {
                $filesystem->writeStream((string) $path, $stream);
                fclose($stream);
            }

            $this->feedManager->flush();
            $this->feedManager->clear();

            $this->eventDispatcher->dispatch(new BatchGeneratedEvent($feed));
        } catch (GenerateBatchException $e) {
            $e->setFeedId((int) $feed->getId());
            $e->setChannelCode((string) $channel->getCode());
            $e->setLocaleCode((string) $locale->getCode());

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
            $newException->setFeedId((int) $feed->getId());
            $newException->setChannelCode((string) $channel->getCode());
            $newException->setLocaleCode((string) $locale->getCode());

            throw $newException;
        } finally {
            $this->resetRequestContext();
        }
    }

    private function setTemporaryRequestContext(ChannelInterface $channel): void
    {
        $this->initialRequestContext = $this->urlGenerator->getContext();

        $requestContext = new RequestContext();
        $requestContext->setScheme('https')
            ->setHost((string) $channel->getHostname())
        ;
        $this->urlGenerator->setContext($requestContext);
    }

    private function resetRequestContext(): void
    {
        $this->urlGenerator->setContext($this->initialRequestContext);
    }

    private function getWorkflow(FeedInterface $feed): WorkflowInterface
    {
        try {
            $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        } catch (InvalidArgumentException $e) {
            throw new UnrecoverableMessageHandlingException(
                'An error occurred when trying to get the workflow for the feed',
                0,
                $e,
            );
        }

        return $workflow;
    }

    /**
     * @return resource
     */
    private function openStream()
    {
        // needs to be w+ since we use the same stream later to read from
        $resource = fopen('php://temp', 'w+b');

        if (!is_resource($resource)) {
            throw new \RuntimeException('Could not open the stream');
        }

        return $resource;
    }

    private function applyErrorTransition(WorkflowInterface $workflow, FeedInterface $feed): void
    {
        // if the feed is already errored we won't want to throw an exception
        if ($feed->isErrored()) {
            return;
        }

        if (!$workflow->can($feed, FeedGraph::TRANSITION_ERRORED)) {
            throw new InvalidArgumentException(sprintf(
                'The transition "%s" could not be applied. State was: "%s"',
                FeedGraph::TRANSITION_ERRORED,
                $feed->getState(),
            ));
        }

        $workflow->apply($feed, FeedGraph::TRANSITION_ERRORED);
    }
}
