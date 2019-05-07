<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template\Registry;

use InvalidArgumentException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Template\Template;

final class TemplateRegistry implements TemplateRegistryInterface
{
    /**
     * @var Template[]
     */
    private $templates = [];

    public function __construct(Template ...$templates)
    {
        $this->templates = $templates;
    }

    public function all(): array
    {
        return $this->templates;
    }

    /**
     * {@inheritdoc}
     *
     * @throws StringsException
     */
    public function register(Template $template): void
    {
        if ($this->has($template)) {
            throw new InvalidArgumentException(sprintf('The template %s is already registered', $template->getType()));
        }

        $this->templates[$template->getType()] = $template;
    }

    public function has($template): bool
    {
        if ($template instanceof Template) {
            $template = $template->getType();
        }

        return isset($this->templates[$template]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws StringsException
     */
    public function get(string $type): Template
    {
        if (!$this->has($type)) {
            throw new InvalidArgumentException(sprintf('Template %s does not exist', $type));
        }

        return $this->templates[$type];
    }
}
