<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import('vendor/sylius-labs/coding-standard/ecs.php');
    $containerConfigurator->parameters()->set(Option::PATHS, [
        'src', 'tests', 'spec'
    ]);
    $containerConfigurator->parameters()->set(Option::SKIP, [
        'tests/Application/**',
    ]);

    /**
     * Was added to fix this exception:
     *
     * PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException: [binary_operator_spaces] Invalid configuration:
     * The options "align_double_arrow", "align_equals" do not exist. Defined options are: "default", "operators".
     * in vendor/friendsofphp/php-cs-fixer/src/AbstractFixer.php on line 155
     */
    $containerConfigurator->services()->set(BinaryOperatorSpacesFixer::class);
};
