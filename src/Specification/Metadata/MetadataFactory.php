<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Metadata;

use Setono\SyliusFeedPlugin\Specification\Attribute\Specification as SpecificationAttribute;
use Setono\SyliusFeedPlugin\Specification\Specification;
use Webmozart\Assert\Assert;

final class MetadataFactory implements MetadataFactoryInterface
{
    public function getMetadataFor(Specification $specification): Metadata
    {
        $reflectionClass = new \ReflectionClass($specification);

        $specificationAttribute = $this->getSpecificationAttribute($reflectionClass);

        return new Metadata(
            $specificationAttribute->name,
            $specificationAttribute->format,
        );
    }

    private function getSpecificationAttribute(\ReflectionClass $reflectionClass): SpecificationAttribute
    {
        $attributes = $reflectionClass->getAttributes(SpecificationAttribute::class);
        Assert::count($attributes, 1);

        return $attributes[0]->newInstance();
    }
}
