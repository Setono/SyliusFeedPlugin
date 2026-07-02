<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPackage('league/flysystem-bundle', [ErrorType::UNUSED_DEPENDENCY]) // this is used to inject the filesystem service
    // False positives: `XmlWriter` is the plugin's own class referenced in a `{@see ...}` docblock
    // and `LOCALE` is the ScopeDimension enum case — the older php-parser pulled on the lowest-deps
    // leg misresolves both to class-like symbols; on the highest leg they resolve fine (hence the
    // ignore is a no-op there, which is why unmatched-ignore reporting is disabled).
    ->ignoreUnknownClasses(['XmlWriter', 'LOCALE'])
    ->disableReportingUnmatchedIgnores()
    ;
