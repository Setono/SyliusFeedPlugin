<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\DataMapper\SpecificationDataMapperInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Serializer\SpecificationSerializerInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final class FeedPartGenerator implements FeedPartGeneratorInterface
{
    public function __construct(
        private readonly SpecificationDataMapperInterface $specificationDataMapper,
        private readonly SpecificationSerializerInterface $specificationSerializer,
        private readonly FilesystemOperator $filesystemOperator,
    ) {
    }

    public function generate(FeedInterface $feed, iterable $objects): string
    {
        $specificationClass = $feed->getSpecification();
        Assert::notNull($specificationClass);

        $fp = fopen('php://memory', 'wb');

        foreach ($objects as $object) {
            $specification = new $specificationClass();

            $this->specificationDataMapper->map($feed, $object, $specification);

            fwrite($fp, $this->specificationSerializer->serialize($feed, $specification));
        }

        $path = sprintf('feed-%d-batch-%s', (int) $feed->getId(), Uuid::v4());
        $this->filesystemOperator->writeStream($path, $fp);

        fclose($fp);

        return $path;
    }
}
