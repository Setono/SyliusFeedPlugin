<?php

declare(strict_types=1);

use Setono\SyliusFeedPlugin\Tests\Application\Kernel;

require __DIR__ . '/../../vendor/autoload.php';

$kernel = new Kernel('test', true);
$kernel->boot();

/** @phpstan-ignore method.notFound,method.nonObject */
return $kernel->getContainer()->get('doctrine')->getManager();
