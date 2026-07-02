<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

/**
 * The outcome of generating one context's feed file (§11): the canonical/primary path, how many
 * items were included vs excluded, the total size of the produced file(s), a bounded sample of
 * per-item exclusion reasons, and every file actually written (§12) — a single file when the context
 * did not split, or the parts plus an optional manifest when it did, so delivery can push the whole
 * set.
 */
final class GenerationResult
{
    /** @var list<string> */
    public readonly array $paths;

    /**
     * @param list<array{item: ?string, reason: string}> $errors
     * @param list<string>|null                          $paths  all written files (parts + manifest); defaults to [$path]
     */
    public function __construct(
        public readonly string $path,
        public readonly int $itemCount,
        public readonly int $excludedCount,
        public readonly int $bytes = 0,
        public readonly array $errors = [],
        ?array $paths = null,
    ) {
        $this->paths = $paths ?? [$path];
    }
}
