<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

interface FeedGeneratorInterface
{
    /**
     * Generates one context's feed file inline, streaming it to storage (§6). This is the default,
     * fast path and is unchanged by fan-out.
     */
    public function generate(FeedInterface $feed, FeedContext $context): GenerationResult;

    /**
     * The per-context item stream — resolve → transform → event → validate — optionally constrained
     * to a single chunk's id range (§6.3). Exposed so a fan-out chunk can render exactly its slice.
     *
     * @return iterable<FeedItem>
     */
    public function items(FeedInterface $feed, FeedContext $context, ?ChunkRange $range = null): iterable;

    /**
     * Renders one fan-out chunk as a body-only partial for the given id range (§6.3): the item bytes
     * only, written to `{code}/{contextKey}.chunk-{index}.{ext}`, reusing the same per-item rendering
     * as {@see generate()}. Returns the chunk's item/exclusion counts for the barrier bookkeeping.
     */
    public function generateChunk(FeedInterface $feed, FeedContext $context, ChunkRange $range, int $chunkIndex): ChunkRenderResult;

    /**
     * Concatenates the ordered body-only partials (chunk 0..N-1) into the canonical context file at
     * `{code}/{contextKey}.{ext}` — preamble + each partial's bytes in order + epilogue — reusing the
     * same preamble/epilogue rendering as {@see generate()} so the output is byte-identical to the
     * inline single-file path. The partials are deleted afterwards.
     */
    public function finalizeChunks(FeedInterface $feed, FeedContext $context, int $chunkCount): OutputResult;
}
