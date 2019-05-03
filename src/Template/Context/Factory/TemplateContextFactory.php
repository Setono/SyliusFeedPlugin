<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template\Context\Factory;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Template\Context\TemplateContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

final class TemplateContextFactory implements TemplateContextFactoryInterface
{
    public function create(string $class, FeedInterface $feed, ChannelInterface $channel, LocaleInterface $locale): TemplateContextInterface
    {
        if (!is_a($class, TemplateContextInterface::class, true)) {
            throw new \InvalidArgumentException('Wrong type of $class'); // todo better exception
        }

        /** @var TemplateContextInterface $context */
        $context = new $class();
        $context->setFeed($feed);
        $context->setChannel($channel);
        $context->setLocale($locale);

        return $context;
    }
}
