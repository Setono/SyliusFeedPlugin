<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use League\Flysystem\FilesystemInterface;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use SplFileInfo;

final class TemporaryFeedPathGenerator implements FeedPathGeneratorInterface
{
    public const BASE_FILENAME = '_feed';

    /**
     * The returned file is a directory
     *
     * @throws StringsException
     */
    public function generate(FeedInterface $feed, string $channelCode, string $localeCode): SplFileInfo
    {
        return new SplFileInfo(sprintf('%s/%s/%s', $feed->getUuid(), $channelCode, $localeCode));
    }

    public static function getBaseFile(SplFileInfo $dir): SplFileInfo
    {
        return new SplFileInfo($dir->getPathname() . '/' . self::BASE_FILENAME);
    }

    public static function getPartialFile(SplFileInfo $dir, FilesystemInterface $filesystem): SplFileInfo
    {
        do {
            $path = $dir->getPathname() . '/' . uniqid('partial-', true);
        } while ($filesystem->has($path));

        return new SplFileInfo($path);
    }
}
