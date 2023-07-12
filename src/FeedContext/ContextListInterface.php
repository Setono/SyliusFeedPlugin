<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext;

use Countable;
use Traversable;

/**
 * @extends Traversable<array-key, array>
 */
interface ContextListInterface extends Traversable, Countable
{
    public function add(array $context): void;
}
