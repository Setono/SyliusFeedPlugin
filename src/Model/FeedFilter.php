<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

class FeedFilter implements FeedFilterInterface
{
    protected ?int $id = null;

    protected ?FeedSourceInterface $source = null;

    protected ?string $field = null;

    protected ?string $operator = null;

    protected mixed $value = null;

    protected string $action = self::ACTION_INCLUDE;

    protected string $stage = self::STAGE_PRE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?FeedSourceInterface
    {
        return $this->source;
    }

    public function setSource(?FeedSourceInterface $source): void
    {
        $this->source = $source;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(?string $field): void
    {
        $this->field = $field;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setOperator(?string $operator): void
    {
        $this->operator = $operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function setStage(string $stage): void
    {
        $this->stage = $stage;
    }
}
