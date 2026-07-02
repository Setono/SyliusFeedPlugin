<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

/**
 * Creates and persists a {@see FeedContextResultInterface} from a completed {@see GenerationResult}
 * (§11). Uses the resource factory + ManagerRegistry so the concrete model stays overridable and
 * the correct manager is resolved per entity.
 */
final class FeedContextResultRecorder implements FeedContextResultRecorderInterface
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly FactoryInterface $feedContextResultFactory,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function record(FeedInterface $feed, string $contextKey, GenerationResult $result): FeedContextResultInterface
    {
        $feedContextResult = $this->feedContextResultFactory->createNew();
        Assert::isInstanceOf($feedContextResult, FeedContextResultInterface::class);

        $feedContextResult->setFeed($feed);
        $feedContextResult->setContextKey($contextKey);
        $feedContextResult->setItemCount($result->itemCount);
        $feedContextResult->setExcludedCount($result->excludedCount);
        $feedContextResult->setBytes($result->bytes);
        $feedContextResult->setPaths($result->paths);
        $feedContextResult->setErrors($result->errors);

        $manager = $this->getManager($feedContextResult);
        $manager->persist($feedContextResult);
        $manager->flush();

        return $feedContextResult;
    }
}
