<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message;

/**
 * Marker for messages dispatched on the plugin's command bus (`setono_sylius_feed.command_bus`).
 * Apps may route these to an async transport; by default they are handled synchronously.
 */
interface CommandInterface
{
}
