<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template\Context;

use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;

final class GoogleShoppingTemplateContext extends TemplateContext
{
    /**
     * @var DataSourceInterface
     */
    private $dataSource;

    public function __construct(DataSourceInterface $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    public function asArray(): array
    {
        return [
            'title' => 'Google Shopping feed',
            'url' => 'https://example.com',
            'description' => 'Description of the Google Shopping feed',
            'items' => $this->dataSource->getData(),
        ];
    }
}
