<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Taxon;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

/**
 * The translated taxon slug for the context locale (§8.4).
 */
final class TaxonSlugResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'slug';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.slug';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, TaxonInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        $locale = $context->getLocale();
        if (null === $locale || !$entity instanceof TaxonInterface) {
            return null;
        }

        return $entity->getTranslation($locale)->getSlug();
    }
}
