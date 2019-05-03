<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Template\Context\Factory\TemplateContextFactoryInterface;
use Setono\SyliusFeedPlugin\Template\Context\TemplateContextInterface;
use Setono\SyliusFeedPlugin\Template\TemplateInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Filesystem\Filesystem;

final class FeedGenerator implements FeedGeneratorInterface
{
    /**
     * @var TwigEngine
     */
    private $engine;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var TemplateContextFactoryInterface
     */
    private $templateContextFactory;

    public function __construct(TwigEngine $engine, Filesystem $filesystem, TemplateContextFactoryInterface $templateContextFactory)
    {
        $this->engine = $engine;
        $this->filesystem = $filesystem;
        $this->templateContextFactory = $templateContextFactory;
    }

    public function generate(FeedInterface $feed): void
    {
        /** @var TemplateInterface $template */
        $template = $this->templateRegistry->get($feed->getTemplate());

        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $path = $this->pathProvider->provide($channel, $locale, $feed->getSlug());
                $tempPath = $this->pathProvider->provideTemp($channel, $locale, $feed->getSlug());

                /** @var TemplateContextInterface $context */
                $context = $this->templateContextFactory->create($template->getContext(), $feed, $channel, $locale);

                $fp = fopen($tempPath, 'wb');

                ob_start(static function ($buffer) use ($fp) {
                    fwrite($fp, $buffer);
                }, 1024);

                $this->engine->stream($template->getPath(), $context->asArray());

                ob_end_clean();

                fclose($fp);

                $this->filesystem->rename($tempPath, $path, true);
            }
        }
    }
}
