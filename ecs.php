<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $config): void {
    $config->import('vendor/sylius-labs/coding-standard/ecs.php');
    $config->parameters()->set(Option::PATHS, [
        'src', 'tests', 'spec'
    ]);
    $config->parameters()->set(Option::SKIP, [
        'tests/Application/**',
    ]);
};
