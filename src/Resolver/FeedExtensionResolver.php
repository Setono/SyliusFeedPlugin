<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Resolver;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Twig\Environment;

final class FeedExtensionResolver implements FeedExtensionResolverInterface
{
    private FeedTypeRegistryInterface $feedTypeRegistry;

    private Environment $twig;

    public function __construct(FeedTypeRegistryInterface $feedTypeRegistry, Environment $twig)
    {
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->twig = $twig;
    }

    public function resolve(FeedInterface $feed): string
    {
        $feedType = $this->feedTypeRegistry->get((string) $feed->getFeedType());

        $template = $this->twig->load($feedType->getTemplate());

        return $template->renderBlock('extension');
    }
}
