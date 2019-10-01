<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Twig;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use Safe\Exceptions\PcreException;
use function Safe\preg_replace;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class Extension extends AbstractExtension
{
    /** @var RequestStack */
    private $requestStack;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        SerializerInterface $serializer
    ) {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->serializer = $serializer;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('setono_sylius_feed_remove_empty_tags', [$this, 'removeEmptyTags']),
            new TwigFilter('setono_sylius_feed_json', [$this, 'json']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('setono_sylius_feed_generate_feed_url', [$this, 'generateFeedUrl']),
        ];
    }

    /**
     * @throws PcreException
     */
    public function removeEmptyTags(string $xml): string
    {
        return preg_replace('#<[^/>][^>]*></[^>]+>#', '', $xml);
    }

    /**
     * @param array|object|mixed $data
     */
    public function json($data): string
    {
        return $this->serializer->serialize($data, 'json', [
            JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }

    public function generateFeedUrl(FeedInterface $feed, ChannelInterface $channel, LocaleInterface $locale): string
    {
        $path = $this->urlGenerator->generate('setono_sylius_feed_shop_feed_show', [
            '_locale' => $locale->getCode(),
            'uuid' => $feed->getUuid(),
        ]);

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