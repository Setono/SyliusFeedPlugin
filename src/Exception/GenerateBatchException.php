<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Exception;

use RuntimeException;
use Throwable;
use Webmozart\Assert\Assert;

final class GenerateBatchException extends RuntimeException implements ExceptionInterface
{
    private readonly string $originalMessage;

    private ?int $feedId = null;

    /** @var mixed */
    private $resourceId;

    private ?string $channelCode = null;

    private ?string $localeCode = null;

    public function __construct(string $message, Throwable $previous)
    {
        parent::__construct($message, 0, $previous);

        $this->originalMessage = $message;
    }

    public function getFeedId(): ?int
    {
        return $this->feedId;
    }

    public function setFeedId(int $feedId): void
    {
        $this->feedId = $feedId;

        $this->updateMessage();
    }

    /**
     * @return mixed
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @param mixed $resourceId
     */
    public function setResourceId($resourceId): void
    {
        Assert::scalar($resourceId);

        $this->resourceId = $resourceId;

        $this->updateMessage();
    }

    public function getChannelCode(): ?string
    {
        return $this->channelCode;
    }

    public function setChannelCode(string $channelCode): void
    {
        $this->channelCode = $channelCode;

        $this->updateMessage();
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(string $localeCode): void
    {
        $this->localeCode = $localeCode;

        $this->updateMessage();
    }

    private function updateMessage(): void
    {
        $this->message = $this->originalMessage;

        if (null !== $this->feedId) {
            $this->message .= ' | Feed: ' . $this->feedId;
        }

        if (null !== $this->resourceId) {
            $this->message .= ' | Resource id: ' . $this->resourceId;
        }

        if (null !== $this->channelCode) {
            $this->message .= ' | Channel code: ' . $this->channelCode;
        }

        if (null !== $this->localeCode) {
            $this->message .= ' | Locale code: ' . $this->localeCode;
        }
    }
}
