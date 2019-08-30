<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface ViolationInterface extends ResourceInterface
{
    public const SEVERITY_ERROR = 'error';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_NOTICE = 'notice';

    public function getFeed(): ?FeedInterface;

    public function setFeed(?FeedInterface $feed): void;

    public function getChannel(): ChannelInterface;

    public function setChannel(ChannelInterface $channel): void;

    public function getLocale(): LocaleInterface;

    public function setLocale(LocaleInterface $locale): void;

    public function getSeverity(): string;

    public function setSeverity(string $severity): void;

    public function getMessage(): string;

    public function setMessage(string $message): void;
}
