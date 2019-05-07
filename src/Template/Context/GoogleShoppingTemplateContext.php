<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template\Context;

use Generator;

final class GoogleShoppingTemplateContext extends TemplateContext
{
    public function asArray(): array
    {
        return [
            'title' => 'Google Shopping feed',
            'url' => 'https://example.com',
            'description' => 'Description of the Google Shopping feed',
            'items' => static function (): Generator {
                for ($i = 0; $i < 10; ++$i) {
                    yield $i;
                }
            },
        ];
    }
}
