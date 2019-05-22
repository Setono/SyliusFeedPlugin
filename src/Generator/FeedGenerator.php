<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Template\Context\Factory\TemplateContextFactoryInterface;
use Setono\SyliusFeedPlugin\Template\Context\GoogleShoppingTemplateContext;
use Setono\SyliusFeedPlugin\Template\Context\TemplateContextInterface;
use Setono\SyliusFeedPlugin\Template\Registry\TemplateRegistryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Error\Error;

final class FeedGenerator implements FeedGeneratorInterface
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var TemplateContextFactoryInterface
     */
    private $templateContextFactory;

    /**
     * @var TemplateRegistryInterface
     */
    private $templateRegistry;
    /**
     * @var GoogleShoppingTemplateContext
     */
    private $templateContext;

    public function __construct(
        Environment $twig,
        Filesystem $filesystem,
        TemplateContextFactoryInterface $templateContextFactory,
        TemplateRegistryInterface $templateRegistry,
        GoogleShoppingTemplateContext $templateContext // todo remove this
    ) {
        $this->twig = $twig;
        $this->filesystem = $filesystem;
        $this->templateContextFactory = $templateContextFactory;
        $this->templateRegistry = $templateRegistry;
        $this->templateContext = $templateContext;
    }

    /**
     * @param FeedInterface $feed
     *
     * @throws Error
     */
    public function generate(FeedInterface $feed): void
    {
        $template = $this->templateRegistry->get($feed->getTemplate());


        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $path = 'path.xml';
                $tempPath = 'path.temp.xml';
//                $path = $this->pathProvider->provide($channel, $locale, $feed);
//                $tempPath = $this->pathProvider->provideTemp($channel, $locale, $feed);

                /** @var TemplateContextInterface $context */
                //$context = $this->templateContextFactory->create($template->getContext(), $feed, $channel, $locale);
                $context = $this->templateContext;

                $fp = fopen($tempPath, 'wb');

                ob_start(static function ($buffer) use ($fp) {
                    fwrite($fp, $buffer);
                }, 1024);

                $this->twig->display($template->getPath(), $context->asArray());

                ob_end_clean();

                fclose($fp);

                $this->filesystem->rename($tempPath, $path, true);
            }
        }
    }
}
