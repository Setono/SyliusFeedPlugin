<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template;

interface TemplateInterface
{
    /**
     * Returns the context class
     *
     * @return string
     */
    public function getContext(): string;

    /**
     * Returns the template path
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Returns the type of template. This has to be unique across all templates so use namespaces
     *
     * @return string
     */
    public static function getType(): string;
}
