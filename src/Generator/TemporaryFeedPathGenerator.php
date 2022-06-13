<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use SplFileInfo;
use Webmozart\Assert\Assert;

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

    public static function getPartialFile(SplFileInfo $dir, FilesystemOperator $filesystem): SplFileInfo
    {
        do {
            $path = $dir->getPathname() . '/' . uniqid('partial-', true);
        } while ($filesystem->fileExists($path));

        return new SplFileInfo($path);
    }
}
