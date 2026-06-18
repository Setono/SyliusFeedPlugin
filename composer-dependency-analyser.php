<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPackage('league/flysystem-bundle', [ErrorType::UNUSED_DEPENDENCY]) // this is used to inject the filesystem service
    ;
