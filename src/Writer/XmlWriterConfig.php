<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

/**
 * Structural configuration for the {@see XmlWriter} (§7): the document root and its attributes, the
 * declared namespaces, an optional wrapper element (e.g. the RSS `channel`), the per-item element
 * name, and the feed-level metadata rendered inside the preamble.
 */
final class XmlWriterConfig implements WriterConfigInterface
{
    /**
     * @param array<string, string> $rootAttributes
     * @param array<string, string> $namespaces prefix => URI (an empty prefix declares the default namespace)
     * @param array<string, string> $preamble element name => value, rendered inside the wrapper
     */
    public function __construct(
        public readonly string $rootElement = 'feed',
        public readonly array $rootAttributes = [],
        public readonly array $namespaces = [],
        public readonly ?string $wrapperElement = null,
        public readonly string $itemElement = 'item',
        public readonly array $preamble = [],
    ) {
    }

    public function withFeedMetadata(array $metadata): self
    {
        return new self(
            $this->rootElement,
            $this->rootAttributes,
            $this->namespaces,
            $this->wrapperElement,
            $this->itemElement,
            $metadata,
        );
    }
}
