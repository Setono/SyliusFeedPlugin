<?php

declare(strict_types=1);

use Setono\SyliusFeedPlugin\Tests\Application\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../../vendor/autoload.php';

// Load the test application's environment so a (re)compiled container can resolve env vars
// such as APP_SECRET when PHPStan boots the kernel.
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(__DIR__ . '/../Application/.env');

$kernel = new Kernel('test', true);
$kernel->boot();

/** @phpstan-ignore method.notFound,method.nonObject */
return $kernel->getContainer()->get('doctrine')->getManager();
