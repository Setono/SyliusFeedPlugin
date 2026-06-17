<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Context;

use Sylius\Component\Core\Model\ChannelInterface;

/**
 * A scope combination producing one output file (or split set). Any of the three
 * dimensions may be null when the owning feed type does not declare it (§4.2, §6.2).
 */
final class FeedContext
{
    public function __construct(
        private readonly ?ChannelInterface $channel = null,
        private readonly ?string $locale = null,
        private readonly ?string $currencyCode = null,
    ) {
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * A stable, filesystem/URL-safe identifier for this context, e.g. "default_en_US".
     * Only the present (non-null) dimensions contribute, so it is deterministic for any
     * combination of declared dimensions.
     */
    public function key(): string
    {
        $parts = [];

        if (null !== $this->channel) {
            $parts[] = (string) $this->channel->getCode();
        }

        if (null !== $this->locale) {
            $parts[] = $this->locale;
        }

        if (null !== $this->currencyCode) {
            $parts[] = $this->currencyCode;
        }

        if ([] === $parts) {
            return 'default';
        }

        // Lowercased so the key is stable across case-insensitive filesystems and in URLs.
        return strtolower(implode('_', $parts));
    }
}
