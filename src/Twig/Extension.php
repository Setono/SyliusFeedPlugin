<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Twig;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class Extension extends AbstractExtension
{
    private RequestStack $requestStack;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
    ) {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('setono_sylius_feed_remove_empty_tags', [$this, 'removeEmptyTags']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('setono_sylius_feed_generate_feed_url', [$this, 'generateFeedUrl']),
        ];
    }

    public function removeEmptyTags(string $xml): string
    {
        return preg_replace('#<[^/>][^>]*></[^>]+>#', '', $xml);
    }

    public function generateFeedUrl(FeedInterface $feed, ChannelInterface $channel, LocaleInterface $locale): string
    {
        $path = $this->urlGenerator->generate('setono_sylius_feed_shop_feed_show', [
            '_locale' => $locale->getCode(),
            'code' => $feed->getCode(),
        ]);

        // todo maybe inject request context into router instead to 'make it right'
        return $this->getScheme() . '://' . $channel->getHostname() . $path;
    }

    private function getScheme(): string
    {
        $request = $this->requestStack->getMasterRequest();
        if (null === $request) {
            return 'https';
        }

        return $request->getScheme();
    }
}
