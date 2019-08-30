<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataProvider;

use Setono\DoctrineORMBatcher\Batch\BatchInterface;
use Setono\DoctrineORMBatcher\Batch\CollectionBatchInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface DataProviderInterface
{
    /**
     * Will return an iterable of ids
     *
     * @return iterable<CollectionBatchInterface>
     */
    public function getBatches(): iterable;

    /**
     * Returns the number of batches
     */
    public function getBatchCount(): int;

    /**
     * This will return the items based on the given batch
     *
     * @return iterable<ResourceInterface>
     */
    public function getItems(BatchInterface $batch): iterable;
}
