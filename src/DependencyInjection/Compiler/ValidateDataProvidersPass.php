<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection\Compiler;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ValidateDataProvidersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $seen = [];

        foreach ($container->findTaggedServiceIds('setono_sylius_feed.data_provider') as $id => $tagged) {
            if (count($tagged) === 0) {
                throw new InvalidArgumentException(sprintf('The service %s needs the code attribute. Something like this: <tag name="setono_sylius_feed.data_provider" code="insert code here"/>', $id));
            }

            foreach ($tagged as $attributes) {
                if (!isset($attributes['code'])) {
                    throw new InvalidArgumentException(sprintf('The service %s needs the code attribute. Something like this: <tag name="setono_sylius_feed.data_provider" code="insert code here"/>', $id));
                }

                $code = $attributes['code'];

                if (isset($seen[$code])) {
                    throw new InvalidArgumentException(sprintf('There is already a data provider with the given code, "%s"', $code));
                }

                $seen[$code] = true;
            }
        }
    }
}
