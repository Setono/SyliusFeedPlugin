<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template;

final class Template
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $context;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $label;

    public function __construct(string $type, string $context, string $path, string $label)
    {
        $this->type = $type;
        $this->context = $context;
        $this->path = $path;
        $this->label = $label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
