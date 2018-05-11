<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class LoevgaardSyliusFeedPlugin extends Bundle
{
    use SyliusPluginTrait;
}
