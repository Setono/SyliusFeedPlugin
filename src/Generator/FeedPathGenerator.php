<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Resolver\FeedExtensionResolverInterface;
use SplFileInfo;
use Webmozart\Assert\Assert;

final class FeedPathGenerator implements FeedPathGeneratorInterface
{
    private FeedExtensionResolverInterface $feedExtensionResolver;

    public function __construct(FeedExtensionResolverInterface $feedExtensionResolver)
    {
        $this->feedExtensionResolver = $feedExtensionResolver;
    }

    /**
     * The returned file is a file (i.e. not a directory)
     */
    public function generate(FeedInterface $feed, string $channelCode, string $localeCode): SplFileInfo
    {
        Assert::notEmpty($channelCode);
        Assert::notEmpty($localeCode);

        $ext = $this->feedExtensionResolver->resolve($feed);

        return new SplFileInfo(sprintf('%s/%s/%s.%s', $channelCode, $localeCode, $feed->getCode(), $ext));
    }
}
