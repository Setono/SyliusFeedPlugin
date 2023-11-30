<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext\Google\Shopping;

use Setono\SyliusFeedPlugin\FeedContext\FeedContextInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

class FeedContext implements FeedContextInterface
{
    public function getContext(FeedInterface $feed, ChannelInterface $channel, LocaleInterface $locale): array
    {
        return [
            'title' => $this->getTitle($channel),
            'url' => 'https://' . $channel->getHostname(),
            'description' => '',
        ];
    }

    private function getTitle(ChannelInterface $channel): string
    {
        $billingData = $channel->getShopBillingData();
        if (null !== $billingData && $billingData->getCompany() !== null) {
            return $billingData->getCompany();
        }

        return (string) $channel->getHostname();
    }
}
