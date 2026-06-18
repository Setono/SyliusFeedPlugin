<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection\Compiler;

use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;

/**
 * Aliases the configured storage parameters (feed, feed_tmp) to their Flysystem service ids and
 * validates that each references a {@see FilesystemOperator}, so the rest of the plugin can depend
 * on the alias rather than a concrete storage service.
 */
final class RegisterFilesystemPass implements CompilerPassInterface
{
    private const PARAMETERS = ['setono_sylius_feed.storage.feed', 'setono_sylius_feed.storage.feed_tmp'];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::PARAMETERS as $parameter) {
            if (!$container->hasParameter($parameter)) {
                continue;
            }

            $serviceId = $container->getParameter($parameter);
            Assert::string($serviceId);

            if (!$container->hasDefinition($serviceId)) {
                throw new InvalidArgumentException(sprintf('No service definition exists with id "%s"', $serviceId));
            }

            $class = $container->getDefinition($serviceId)->getClass();
            Assert::notNull($class);

            if (!is_a($class, FilesystemOperator::class, true)) {
                throw new InvalidDefinitionException(sprintf(
                    'The config parameter "%s" references the service "%s" of class "%s", which is not an instance of %s.',
                    $parameter,
                    $serviceId,
                    $class,
                    FilesystemOperator::class,
                ));
            }

            $container->setAlias($parameter, $serviceId);
        }
    }
}
