<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection\Compiler;

use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use function Safe\sprintf;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;

final class RegisterFilesystemPass implements CompilerPassInterface
{
    private const PARAMETERS = ['setono_sylius_feed.storage.feed', 'setono_sylius_feed.storage.feed_tmp'];

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
            /** @phpstan-ignore-next-line */
            if (!$container->hasDefinition($parameterValue)) {
                throw new InvalidArgumentException(sprintf('No service definition exists with id "%s"', $parameterValue));
            }

            /** @phpstan-ignore-next-line */
            $definitionClass = $container->getDefinition($parameterValue)->getClass();
            Assert::notNull($definitionClass);

            if (!is_a($definitionClass, FilesystemInterface::class, true)) {
                throw new InvalidDefinitionException(sprintf(
                    'The config parameter "%s" references a service %s, which is not an instance of %s. Fix this by creating a valid service that implements %s.',
                    $parameter,
                    $definitionClass,
                    FilesystemInterface::class,
                    FilesystemInterface::class
                ));
            }

            /** @phpstan-ignore-next-line */
            $container->setAlias($parameter, $parameterValue);
        }
    }
}
