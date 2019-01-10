<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template;

interface TemplateInterface
{
    /**
     * Returns the template path
     *
     * @return string
     */
    public function getPath(): string;
}
