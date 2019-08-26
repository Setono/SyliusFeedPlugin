<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Registry;

use InvalidArgumentException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;

final class FeedTypeRegistry implements FeedTypeRegistryInterface
{
    /** @var FeedTypeInterface[] */
    private $feedTypes = [];

    /**
     * @throws StringsException
     */
    public function __construct(FeedTypeInterface ...$feedTypes)
    {
        foreach ($feedTypes as $feedType) {
            $this->register($feedType);
        }
    }

    /**
     * @throws StringsException
     */
    private function register(FeedTypeInterface $feedType): void
    {
        $code = $feedType->getCode();

        if ($this->has($code)) {
            throw new InvalidArgumentException(sprintf('A feed with code %s has already been registered', $code));
        }

        $this->feedTypes[$code] = $feedType;
    }

    public function has(string $code): bool
    {
        return isset($this->feedTypes[$code]);
    }

    /**
     * @throws StringsException
     */
    public function get(string $code): FeedTypeInterface
    {
        if (!$this->has($code)) {
            throw new InvalidArgumentException(sprintf('The feed with code %s does not exist', $code));
        }

        return $this->feedTypes[$code];
    }

    public function all(): array
    {
        return $this->feedTypes;
    }
}
