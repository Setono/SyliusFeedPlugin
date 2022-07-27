<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $config): void {
    $config->import('vendor/sylius-labs/coding-standard/ecs.php');
    $config->paths([
        'src', 'tests', 'spec'
    ]);
    $config->skip([
        'tests/Application/**',
    ]);
};
