<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Webmozart\Assert\Assert;

final class FeedWriterRegistry implements FeedWriterRegistryInterface
{
    /** @var array<string, FeedWriterInterface> */
    private array $writers = [];

    /**
     * @param iterable<FeedWriterInterface> $writers
     */
    public function __construct(iterable $writers)
    {
        foreach ($writers as $writer) {
            $format = $writer->getFormat();
            Assert::keyNotExists($this->writers, $format, sprintf('A feed writer for format "%s" is already registered', $format));

            $this->writers[$format] = $writer;
        }
    }

    public function get(string $format): FeedWriterInterface
    {
        Assert::keyExists($this->writers, $format, sprintf('No feed writer for format "%s" is registered', $format));

        return $this->writers[$format];
    }

    public function has(string $format): bool
    {
        return isset($this->writers[$format]);
    }

    public function all(): array
    {
        return $this->writers;
    }
}
