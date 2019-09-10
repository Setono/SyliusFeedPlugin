<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

class Violation implements ViolationInterface
{
    /** @var int */
    protected $id;

    /** @var FeedInterface|null */
    protected $feed;

    /** @var ChannelInterface */
    protected $channel;

    /** @var LocaleInterface */
    protected $locale;

    /** @var string */
    protected $severity = self::SEVERITY_NOTICE;

    /** @var string */
    protected $message;

    /**
     * The data can be anything basically. Just some data that will aid in this specific violation
     *
     * @var mixed|null
     */
    protected $data;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFeed(): ?FeedInterface
    {
        return $this->feed;
    }

    public function setFeed(?FeedInterface $feed): void
    {
        $this->feed = $feed;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getLocale(): LocaleInterface
    {
        return $this->locale;
    }

    public function setLocale(LocaleInterface $locale): void
    {
        $this->locale = $locale;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): void
    {
        $this->severity = $severity;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed|null $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }
}
