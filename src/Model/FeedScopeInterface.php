<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Resource\Model\ResourceInterface;

interface FeedScopeInterface extends ResourceInterface, ChannelAwareInterface
{
    public function getId(): ?int;

    public function getFeed(): ?FeedInterface;

    public function setFeed(?FeedInterface $feed): void;

    public function getLocaleCode(): ?string;

    public function setLocaleCode(?string $localeCode): void;

    public function getCurrencyCode(): ?string;

    public function setCurrencyCode(?string $currencyCode): void;
}
