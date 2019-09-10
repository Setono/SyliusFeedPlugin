<?php
declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ToStringNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = []): string
    {
        return (string) $object;
    }

    public function supportsNormalization($object, $format = null): bool
    {
        if(!is_object($object)) {
            return false;
        }

        if(!method_exists($object, '__toString')) {
            return false;
        }

        return true;
    }

}
