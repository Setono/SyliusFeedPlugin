<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface FeedFilterInterface extends ResourceInterface
{
    public const STAGE_PRE = 'pre';

    public const STAGE_POST = 'post';

    public const ACTION_INCLUDE = 'include';

    public const ACTION_EXCLUDE = 'exclude';

    public function getId(): ?int;

    public function getSource(): ?FeedSourceInterface;

    public function setSource(?FeedSourceInterface $source): void;

    public function getField(): ?string;

    public function setField(?string $field): void;

    public function getOperator(): ?string;

    public function setOperator(?string $operator): void;

    public function getValue(): mixed;

    public function setValue(mixed $value): void;

    public function getAction(): string;

    public function setAction(string $action): void;

    public function getStage(): string;

    public function setStage(string $stage): void;
}
