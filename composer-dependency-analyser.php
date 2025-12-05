<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreUnknownClasses([
        League\Flysystem\FileNotFoundException::class,
        League\Flysystem\FilesystemInterface::class,
        League\Flysystem\RootViolationException::class,
    ])
    ->ignoreErrorsOnPackage('league/flysystem-bundle', [ErrorType::UNUSED_DEPENDENCY]) // this is used to inject the filesystem service
    ->ignoreErrorsOnPackage('setono/doctrine-orm-batcher-bundle', [ErrorType::UNUSED_DEPENDENCY]) // this is used to inject the batcher service
    ;
