<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Lookup\LookupReferenceResolverInterface;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\Mapping\SourceType;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistryInterface;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolverInterface;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluatorInterface;
use Setono\SyliusFeedPlugin\Scripting\ScriptingVariables;
use Setono\SyliusFeedPlugin\Scripting\TwigTemplateRendererInterface;
use Setono\SyliusFeedPlugin\Transformation\TransformationChainInterface;

/**
 * @see FieldMappingEvaluatorInterface
 */
final class FieldMappingEvaluator implements FieldMappingEvaluatorInterface
{
    public function __construct(
        private readonly TransformationChainInterface $transformationChain,
        private readonly ReferenceResolverInterface $referenceResolver,
        private readonly OperatorRegistryInterface $operatorRegistry,
        private readonly ExpressionEvaluatorInterface $expressionEvaluator,
        private readonly TwigTemplateRendererInterface $twigRenderer,
        private readonly LookupReferenceResolverInterface $lookupReferenceResolver,
    ) {
    }

    public function apply(FeedItem $item, iterable $mappings, array $availableFields): void
    {
        // Idempotent: the generator binds this up front (so pre-filters can resolve source fields);
        // this call is the safety net when apply() runs standalone, e.g. in unit tests.
        SourceResolverBinder::bind($item, $availableFields, $this->lookupReferenceResolver);

        foreach ($mappings as $mapping) {
            if (!$this->conditionSatisfied($mapping, $item)) {
                continue;
            }

            $value = $this->transformationChain->apply(
                $this->resolveValue($mapping, $item),
                $mapping->getTransformations(),
                $item,
            );

            if (null !== $value) {
                $item->set($mapping->getOutputField(), $value);
            }
        }
    }

    private function resolveValue(FieldMapping $mapping, FeedItem $item): mixed
    {
        return match ($mapping->getSourceType()) {
            SourceType::LITERAL => $mapping->getSourceValue(),
            SourceType::FIELD => $item->resolveSource($mapping->getSourceValue()),
            SourceType::EXPRESSION => $this->evaluate($mapping->getSourceValue(), $item),
            SourceType::TWIG => $this->render($mapping->getSourceValue(), $item),
        };
    }

    /**
     * A source expression/twig has no in-flight value; it derives one from the item. An evaluation
     * error resolves to null (the field then falls to its transformations/condition) — never a throw.
     */
    private function evaluate(string $expression, FeedItem $item): mixed
    {
        if ('' === $expression) {
            return null;
        }

        try {
            return $this->expressionEvaluator->evaluate($expression, ScriptingVariables::for($item, null));
        } catch (\Throwable) {
            return null;
        }
    }

    private function render(string $template, FeedItem $item): mixed
    {
        if ('' === $template) {
            return null;
        }

        try {
            return $this->twigRenderer->render($template, ScriptingVariables::for($item, null));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The emit condition uses the shared operator vocabulary over reference-resolved operands
     * (§10). A null condition always emits (the FieldMapping::onlyIf shorthand sets operator "true").
     */
    private function conditionSatisfied(FieldMapping $mapping, FeedItem $item): bool
    {
        $condition = $mapping->getCondition();
        if (null === $condition) {
            return true;
        }

        $left = $this->referenceResolver->resolve($condition['field'], null, $item);

        $operand = null;
        if (array_key_exists('value', $condition)) {
            $raw = $condition['value'];
            $operand = is_string($raw) ? $this->referenceResolver->resolve($raw, null, $item) : $raw;
        }

        return $this->operatorRegistry->get($condition['operator'])->matches($left, ['value' => $operand]);
    }
}
