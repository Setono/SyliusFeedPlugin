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
 * entirely from the typed {@see XmlWriterConfig}, so every XML format reuses this one writer (§7).
 *
 * Per-item serialization is generic: a scalar becomes one element, a list becomes repeated
 * elements, and a map becomes a nested element — the writer never switches on the item subclass.
 */
final class XmlWriter implements FeedWriterInterface
{
    /** @var resource|null */
    private $stream;

    private ?\XMLWriter $writer = null;

    private ?XmlWriterConfig $config = null;

    public function getFormat(): string
    {
        return 'xml';
    }

    public function open($stream, FeedContext $context, WriterConfigInterface $config): void
    {
        Assert::isInstanceOf($config, XmlWriterConfig::class);

        $this->stream = $stream;
        $this->config = $config;

        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent(false);
        $this->writer = $writer;
    }

    public function writePreamble(): void
    {
        $writer = $this->getWriter();
        $config = $this->getConfig();

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement($config->rootElement);

        foreach ($config->rootAttributes as $name => $value) {
            $writer->writeAttribute($name, $value);
        }

        foreach ($config->namespaces as $prefix => $uri) {
            $writer->writeAttribute('' === $prefix ? 'xmlns' : 'xmlns:' . $prefix, $uri);
        }

        if (null !== $config->wrapperElement) {
            $writer->startElement($config->wrapperElement);
        }

        foreach ($config->preamble as $name => $value) {
            $writer->writeElement($name, $value);
        }

        $this->flush();
    }

    public function writeItem(FeedItem $item): void
    {
        $writer = $this->getWriter();
        $writer->startElement($this->getConfig()->itemElement);

        foreach ($item->all() as $field => $value) {
            $this->writeField($writer, $field, $value);
        }

        $writer->endElement();
        $this->flush();
    }

    public function writeEpilogue(): void
    {
        $writer = $this->getWriter();

        if (null !== $this->getConfig()->wrapperElement) {
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
        $this->config = null;
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

    private function getConfig(): XmlWriterConfig
    {
        Assert::notNull($this->config, 'The writer must be opened before use');

        return $this->config;
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
