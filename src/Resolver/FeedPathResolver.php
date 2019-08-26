<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Resolver;

use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

final class FeedPathResolver implements FeedPathResolverInterface
{
    /**
     * @throws StringsException
     */
    public function resolve(FeedInterface $feed, string $channelCode, string $localeCode): string
    {
        return sprintf('%s/%s/%s.xml', $channelCode, $localeCode, $feed->getUuid());
    }
}
