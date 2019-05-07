<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template\Registry;

use Setono\SyliusFeedPlugin\Template\Template;

interface TemplateRegistryInterface
{
    /**
     * @return Template[]
     */
    public function all(): array;

    public function register(Template $template): void;

    /**
     * @param string|Template $template
     *
     * @return bool
     */
    public function has($template): bool;

    public function get(string $type): Template;
}
