<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

trait VersionedTrait
{
    protected int $version = 1;

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(?int $version): void
    {
        if (null === $version) {
            $version = 1;
        }

        $this->version = $version;
    }
}
