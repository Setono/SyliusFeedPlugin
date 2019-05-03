<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template;

use Setono\SyliusFeedPlugin\Template\Context\GoogleShoppingTemplateContext;

class GoogleShoppingTemplate implements TemplateInterface
{
    public function getContext(): string
    {
        return GoogleShoppingTemplateContext::class;
    }

    public function getPath(): string
    {
        return '@SetonoSyliusFeedPlugin/Template/google_shopping.xml.twig';
    }

    public static function getType(): string
    {
        return 'setono_sylius_feed_google_shopping';
    }
}
