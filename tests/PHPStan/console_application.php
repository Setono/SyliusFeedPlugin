<?php

declare(strict_types=1);

use Setono\SyliusFeedPlugin\Tests\Application\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require __DIR__ . '/../../vendor/autoload.php';

$kernel = new Kernel('test', true);
$kernel->boot();

return new Application($kernel);
