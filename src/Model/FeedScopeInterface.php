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

    public function getLocale(): ?string;

    public function setLocale(?string $locale): void;

    public function getCurrency(): ?string;

    public function setCurrency(?string $currency): void;
}
