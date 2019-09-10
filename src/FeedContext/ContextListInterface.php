<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext;

use Countable;
use Traversable;

interface ContextListInterface extends Traversable, Countable
{
    public function add(array $context): void;
}
