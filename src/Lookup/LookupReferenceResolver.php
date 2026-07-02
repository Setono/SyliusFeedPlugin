<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Repository\LookupTableRepositoryInterface;

/**
 * @see LookupReferenceResolverInterface
 */
final class LookupReferenceResolver implements LookupReferenceResolverInterface
{
    private const PREFIX = 'lookup:';

    public function __construct(private readonly LookupTableRepositoryInterface $repository)
    {
    }

    public function supports(string $reference): bool
    {
        return str_starts_with($reference, self::PREFIX);
    }

    public function resolve(string $reference, object $entity, FeedContext $context, array $availableFields): mixed
    {
        if (!$this->supports($reference)) {
            return null;
        }

        $parts = explode(':', substr($reference, strlen(self::PREFIX)), 2);
        if (2 !== count($parts)) {
            return null;
        }

        [$code, $column] = $parts;

        $table = $this->repository->findOneByCode($code);
        if (null === $table) {
            return null;
        }

        $joinField = $table->getJoinField();
        if (null === $joinField || !isset($availableFields[$joinField])) {
            return null;
        }

        $key = $availableFields[$joinField]->getResolver()->resolve($entity, $context);
        if (!is_scalar($key)) {
            return null;
        }

        return $table->getColumn((string) $key, $column);
    }
}
