<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Exception;

use RuntimeException;
use Throwable;

final class GenerateBatchException extends RuntimeException implements ExceptionInterface
{
    private const DEFAULT_MESSAGE = 'An error occurred';

    /**
     * @var int
     */
    private $feedId;

    /**
     * @var string
     */
    private $channelCode;

    /**
     * @var string
     */
    private $localeCode;

    public function __construct(string $message, Throwable $previous)
    {
        parent::__construct($message, 0, $previous);
    }

    public function getFeedId(): int
    {
        return $this->feedId;
    }

    public function setFeedId(int $feedId): void
    {
        $this->feedId = $feedId;

        $this->updateMessage();
    }

    public function getChannelCode(): string
    {
        return $this->channelCode;
    }

    public function setChannelCode(string $channelCode): void
    {
        $this->channelCode = $channelCode;

        $this->updateMessage();
    }

    public function getLocaleCode(): string
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
        if(null !== $this->feedId) {
            $this->message .= ' | Feed: '.$this->feedId;
        }

        if(null !== $this->channelCode) {
            $this->message .= ' | Channel code: '.$this->channelCode;
        }

        if(null !== $this->localeCode) {
            $this->message .= ' | Locale code: '.$this->localeCode;
        }
    }
}
