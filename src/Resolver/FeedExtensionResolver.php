<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Resolver;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class FeedExtensionResolver implements FeedExtensionResolverInterface
{
    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    /** @var Environment */
    private $twig;

    public function __construct(FeedTypeRegistryInterface $feedTypeRegistry, Environment $twig)
    {
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->twig = $twig;
    }

    /**
     * @throws Throwable
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function resolve(FeedInterface $feed): string
    {
        $feedType = $this->feedTypeRegistry->get($feed->getFeedType());

        $template = $this->twig->load($feedType->getTemplate());

        return $template->renderBlock('extension');
    }
}
