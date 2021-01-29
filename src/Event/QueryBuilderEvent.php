<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Event;

use Doctrine\ORM\QueryBuilder;
use Setono\SyliusFeedPlugin\DataProvider\DataProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class QueryBuilderEvent extends Event
{
    private DataProviderInterface $dataProvider;

    private QueryBuilder $queryBuilder;

    private ChannelInterface $channel;

    private LocaleInterface $locale;

    public function __construct(
        DataProviderInterface $dataProvider,
        QueryBuilder $queryBuilder,
        ChannelInterface $channel,
        LocaleInterface $locale
    ) {
        $this->dataProvider = $dataProvider;
        $this->queryBuilder = $queryBuilder;
        $this->channel = $channel;
        $this->locale = $locale;
    }

    public function getDataProvider(): DataProviderInterface
    {
        return $this->dataProvider;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getLocale(): LocaleInterface
    {
        return $this->locale;
    }
}
