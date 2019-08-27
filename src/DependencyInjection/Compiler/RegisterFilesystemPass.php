<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection\Compiler;

use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterFilesystemPass implements CompilerPassInterface
{
    private const PARAMETERS = ['setono_sylius_feed.storage.feed', 'setono_sylius_feed.storage.feed_tmp'];

    /**
     * @throws StringsException
     */
    public function process(ContainerBuilder $container): void
    {
        $hasAny = false;

        foreach (self::PARAMETERS as $parameter) {
            if ($container->hasParameter($parameter)) {
                $hasAny = true;
            }
        }

        if (!$hasAny) {
            return;
        }

        foreach (self::PARAMETERS as $parameter) {
            $parameterValue = $container->getParameter($parameter);
            if (!$container->hasDefinition($parameterValue)) {
                throw new InvalidArgumentException(sprintf('No service definition exists with id "%s"', $parameterValue));
            }

            $definition = $container->getDefinition($parameterValue);
            if (!is_a($definition->getClass(), FilesystemInterface::class, true)) {
                throw new InvalidDefinitionException(sprintf(
                    'The config parameter "%s" references a service %s, which is not an instance of %s. Fix this by creating a valid service that implements %s.',
                    $parameter,
                    $definition->getClass(),
                    FilesystemInterface::class,
                    FilesystemInterface::class
                ));
            }

            $container->setAlias($parameter, $parameterValue);
        }
    }
}
