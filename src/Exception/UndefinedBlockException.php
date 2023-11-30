<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Exception;

use InvalidArgumentException;

class UndefinedBlockException extends InvalidArgumentException implements ExceptionInterface
{
    private string $block;

    private array $requiredBlocks;

    /**
     * @param array<array-key, string> $requiredBlocks
     */
    public function __construct(string $block, array $requiredBlocks)
    {
        $message = sprintf('The block %s was not defined. Required blocks are: ["%s"]', $block, implode('", "', $requiredBlocks));

        parent::__construct($message);

        $this->block = $block;
        $this->requiredBlocks = $requiredBlocks;
    }

    public function getBlock(): string
    {
        return $this->block;
    }

    public function getRequiredBlocks(): array
    {
        return $this->requiredBlocks;
    }
}
