<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Resolver;

use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use SplFileInfo;

final class FeedPathResolver implements FeedPathResolverInterface
{
    /** @var FeedExtensionResolverInterface */
    private $feedExtensionResolver;

    public function __construct(FeedExtensionResolverInterface $feedExtensionResolver)
    {
        $this->feedExtensionResolver = $feedExtensionResolver;
    }

    /**
     * @throws StringsException
     */
    public function resolve(FeedInterface $feed, string $channelCode, string $localeCode): SplFileInfo
    {
        $ext = $this->feedExtensionResolver->resolve($feed);

        return new SplFileInfo(sprintf('%s/%s/%s.%s', $channelCode, $localeCode, $feed->getUuid(), $ext));
    }
}
