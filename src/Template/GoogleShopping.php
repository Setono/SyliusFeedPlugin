<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template;

class GoogleShopping implements TemplateInterface
{
    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return '@SetonoSyliusFeedPlugin/Template/google_shopping.xml.twig';
    }
}
