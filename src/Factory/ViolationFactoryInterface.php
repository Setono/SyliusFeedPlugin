<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Factory;

use Setono\SyliusFeedPlugin\Model\ViolationInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

interface ViolationFactoryInterface extends FactoryInterface
{
    /**
     * @param mixed|null $data
     */
    public function createFromConstraintViolation(
        ConstraintViolationInterface $constraintViolation,
        ChannelInterface $channel,
        LocaleInterface $locale,
        $data = null,
    ): ViolationInterface;
}
