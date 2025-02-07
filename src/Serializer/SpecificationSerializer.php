<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Serializer;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Specification\Specification;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final class SpecificationSerializer implements SpecificationSerializerInterface
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function serialize(FeedInterface $feed, Specification $specification): string
    {
        return $this->serializer->serialize($specification, (string) $feed->getFormat(), [
            'feed' => $feed,
            XmlEncoder::FORMAT_OUTPUT => true,
            XmlEncoder::SAVE_OPTIONS => \LIBXML_NOXMLDECL,
            XmlEncoder::ENCODER_IGNORED_NODE_TYPES => [\XML_PI_NODE],
            XmlEncoder::ROOT_NODE_NAME => null,
        ]);
    }
}
