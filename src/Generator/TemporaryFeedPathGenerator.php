<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use SplFileInfo;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress UndefinedDocblockClass
 * @psalm-suppress UndefinedClass
 */
final class TemporaryFeedPathGenerator implements FeedPathGeneratorInterface
{
    public const BASE_FILENAME = '_feed';

    /**
     * The returned file is a directory
     */
    public function generate(FeedInterface $feed, string $channelCode, string $localeCode): SplFileInfo
    {
        Assert::notEmpty($channelCode);
        Assert::notEmpty($localeCode);

        return new SplFileInfo(sprintf('%s/%s/%s', $feed->getCode(), $channelCode, $localeCode));
    }

    public static function getBaseFile(SplFileInfo $dir): SplFileInfo
    {
        return new SplFileInfo($dir->getPathname() . '/' . self::BASE_FILENAME);
    }

    /**
     * @psalm-suppress UndefinedDocblockClass
     *
     * @param FilesystemInterface|FilesystemOperator $filesystem
     */
    public static function getPartialFile(SplFileInfo $dir, $filesystem): SplFileInfo
    {
        if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
            do {
                $path = $dir->getPathname() . '/' . uniqid('partial-', true);
            } while ($filesystem->has($path));
        } elseif ($filesystem instanceof FilesystemOperator) {
            do {
                $path = $dir->getPathname() . '/' . uniqid('partial-', true);
            } while ($filesystem->fileExists($path));
        } else {
            throw new InvalidArgumentException(sprintf(
                'The filesystem must be an instance of %s or %s',
                FilesystemInterface::class,
                FilesystemOperator::class,
            ));
        }

        return new SplFileInfo($path);
    }
}
