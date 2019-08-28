<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Exception;

use InvalidArgumentException;
use Safe\Exceptions\StringsException;

final class UndefinedBlockException extends InvalidArgumentException implements ExceptionInterface
{
    /** @var string */
    private $block;

    /** @var array */
    private $requiredBlocks;

    /**
     * @throws StringsException
     */
    public function __construct(string $block, array $requiredBlocks)
    {
        $message = \Safe\sprintf('The block %s was not defined. Required blocks are: ["%s"]', $block, implode('", "', $requiredBlocks));

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
