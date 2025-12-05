<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Resolver;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Twig\Environment;

final class FeedExtensionResolver implements FeedExtensionResolverInterface
{
    public function __construct(
        private readonly FeedTypeRegistryInterface $feedTypeRegistry,
        private readonly Environment $twig,
    ) {
    }

    public function resolve(FeedInterface $feed): string
    {
        $feedType = $this->feedTypeRegistry->get((string) $feed->getFeedType());

        $template = $this->twig->load($feedType->getTemplate());

        return $template->renderBlock('extension');
    }
}
