<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Webmozart\Assert\Assert;

/**
 * Streams an XML feed using PHP's {@see \XMLWriter} in memory mode, flushing its buffer to the
 * output stream after the preamble and after every item, so the full document is never held in
 * memory (§6.3). The structural shape (root, namespaces, wrapper, item element, preamble) comes
 * entirely from the format config, so every XML format reuses this one writer (§7).
 *
 * Per-item serialization is generic: a scalar becomes one element, a list becomes repeated
 * elements, and a map becomes a nested element — the writer never switches on the item subclass.
 */
final class XmlWriter implements FeedWriterInterface
{
    /** @var resource|null */
    private $stream;

    private ?\XMLWriter $writer = null;

    /** @var array<string, mixed> */
    private array $config = [];

    public function getFormat(): string
    {
        return 'xml';
    }

    public function open($stream, FeedContext $context, array $formatConfig): void
    {
        $this->stream = $stream;
        $this->config = $formatConfig;

        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent(false);
        $this->writer = $writer;
    }

    public function writePreamble(): void
    {
        $writer = $this->getWriter();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement($this->stringConfig('rootElement', 'feed'));

        foreach ($this->arrayConfig('rootAttributes') as $name => $value) {
            $writer->writeAttribute((string) $name, $this->toString($value));
        }

        foreach ($this->arrayConfig('namespaces') as $prefix => $uri) {
            $writer->writeAttribute('' === (string) $prefix ? 'xmlns' : 'xmlns:' . $prefix, $this->toString($uri));
        }

        $wrapper = $this->nullableStringConfig('wrapperElement');
        if (null !== $wrapper) {
            $writer->startElement($wrapper);
        }

        foreach ($this->arrayConfig('preamble') as $name => $value) {
            $writer->writeElement((string) $name, $this->toString($value));
        }

        $this->flush();
    }

    public function writeItem(FeedItem $item): void
    {
        $writer = $this->getWriter();
        $writer->startElement($this->stringConfig('itemElement', 'item'));

        foreach ($item->all() as $field => $value) {
            $this->writeField($writer, $field, $value);
        }

        $writer->endElement();
        $this->flush();
    }

    public function writeEpilogue(): void
    {
        $writer = $this->getWriter();

        if (null !== $this->nullableStringConfig('wrapperElement')) {
            $writer->endElement();
        }

        $writer->endElement();
        $writer->endDocument();
        $this->flush();
    }

    public function close(): void
    {
        $this->flush();
        $this->writer = null;
        $this->stream = null;
        $this->config = [];
    }

    private function writeField(\XMLWriter $writer, string $name, mixed $value): void
    {
        if (null === $value) {
            return;
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                foreach ($value as $element) {
                    $this->writeField($writer, $name, $element);
                }

                return;
            }

            $writer->startElement($name);
            foreach ($value as $childName => $childValue) {
                $this->writeField($writer, (string) $childName, $childValue);
            }
            $writer->endElement();

            return;
        }

        if (is_bool($value)) {
            $writer->writeElement($name, $value ? 'true' : 'false');

            return;
        }

        $writer->writeElement($name, $this->toString($value));
    }

    private function flush(): void
    {
        if (null === $this->writer || null === $this->stream) {
            return;
        }

        $xml = $this->writer->flush(true);
        if (is_string($xml) && '' !== $xml) {
            fwrite($this->stream, $xml);
        }
    }

    private function getWriter(): \XMLWriter
    {
        Assert::notNull($this->writer, 'The writer must be opened before use');

        return $this->writer;
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = $this->config[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    private function nullableStringConfig(string $key): ?string
    {
        $value = $this->config[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function arrayConfig(string $key): array
    {
        $value = $this->config[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    private function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return '';
    }
}
