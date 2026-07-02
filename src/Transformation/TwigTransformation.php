<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;
use Setono\SyliusFeedPlugin\Scripting\ScriptingVariables;
use Setono\SyliusFeedPlugin\Scripting\TwigTemplateRendererInterface;

/**
 * Renders a sandboxed Twig template over the value + item for rich string assembly (§10), e.g.
 * `{{ value|trim }} {{ lookup('badges', entity.getCode(), 'badge')|upper }}`. Output is always a
 * string, so — like the other string transforms — it maps element-wise over a list. A render error
 * is a no-op (returns the element unchanged).
 */
final class TwigTransformation implements TransformationInterface
{
    public const TYPE = 'twig';

    public function __construct(private readonly TwigTemplateRendererInterface $renderer)
    {
    }

    public static function of(string $template): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['template' => $template]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        $template = $params['template'] ?? null;
        if (!is_string($template) || '' === $template) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $element): mixed => $this->render($template, $element, $item), $value);
        }

        return $this->render($template, $value, $item);
    }

    private function render(string $template, mixed $value, FeedItem $item): mixed
    {
        try {
            return $this->renderer->render($template, ScriptingVariables::for($item, $value));
        } catch (\Throwable) {
            return $value;
        }
    }
}
