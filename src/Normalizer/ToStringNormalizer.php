<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Normalizer;

use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

final class ToStringNormalizer implements ContextAwareNormalizerInterface
{
    public function normalize($object, string $format = null, array $context = []): string
    {
        return (string) $object;
    }

    /**
     * To not interfere with other normalizers, we check if the context is within this plugin. See this issue:
     * https://github.com/Setono/SyliusFeedPlugin/issues/19
     *
     * @param mixed|object $object
     */
    public function supportsNormalization($object, string $format = null, array $context = []): bool
    {
        if (!isset($context['setono_sylius_feed_data'])) {
            return false;
        }

        if ($context['setono_sylius_feed_data'] !== true) {
            return false;
        }

        return is_object($object) && method_exists($object, '__toString');
    }
}
