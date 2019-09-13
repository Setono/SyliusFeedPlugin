<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataProvider;

use Setono\DoctrineORMBatcher\Batch\BatchInterface;
use Setono\DoctrineORMBatcher\Batch\CollectionBatchInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface DataProviderInterface
{
    /**
     * This will be the root class of the data provided by this data provider
     */
    public function getClass(): string;

    /**
     * Will return an iterable of ids
     *
     * @return iterable<CollectionBatchInterface>
     */
    public function getBatches(ChannelInterface $channel, LocaleInterface $locale): iterable;

    /**
     * Returns the number of batches
     */
    public function getBatchCount(ChannelInterface $channel, LocaleInterface $locale): int;

    /**
     * This will return the items based on the given batch
     *
     * @return iterable<ResourceInterface>
     */
    public function getItems(BatchInterface $batch): iterable;
}
