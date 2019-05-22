<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

interface FeedInterface extends ResourceInterface, ChannelsAwareInterface, SlugAwareInterface, ToggleableInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(string $name): void;

    public function getTemplate(): ?string;

    public function setTemplate(string $template): void;
}
