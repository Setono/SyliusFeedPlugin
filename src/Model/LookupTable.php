<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

class LookupTable implements LookupTableInterface
{
    protected ?int $id = null;

    protected ?string $code = null;

    protected ?string $name = null;

    protected ?string $sourceType = null;

    /** @var array<string, mixed> */
    protected array $sourceConfig = [];

    protected ?string $keyColumn = null;

    protected ?string $joinField = null;

    protected string $refreshPolicy = self::REFRESH_POLICY_MANUAL;

    protected ?\DateTimeInterface $refreshedAt = null;

    /** @var array<string, array<string, scalar|null>> */
    protected array $rows = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getSourceConfig(): array
    {
        return $this->sourceConfig;
    }

    public function setSourceConfig(array $sourceConfig): void
    {
        $this->sourceConfig = $sourceConfig;
    }

    public function getKeyColumn(): ?string
    {
        return $this->keyColumn;
    }

    public function setKeyColumn(?string $keyColumn): void
    {
        $this->keyColumn = $keyColumn;
    }

    public function getJoinField(): ?string
    {
        return $this->joinField;
    }

    public function setJoinField(?string $joinField): void
    {
        $this->joinField = $joinField;
    }

    public function getRefreshPolicy(): string
    {
        return $this->refreshPolicy;
    }

    public function setRefreshPolicy(string $refreshPolicy): void
    {
        $this->refreshPolicy = $refreshPolicy;
    }

    public function getRefreshedAt(): ?\DateTimeInterface
    {
        return $this->refreshedAt;
    }

    public function setRefreshedAt(?\DateTimeInterface $refreshedAt): void
    {
        $this->refreshedAt = $refreshedAt;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }

    public function getRow(string $key): ?array
    {
        return $this->rows[$key] ?? null;
    }

    public function getColumn(string $key, string $column): mixed
    {
        return $this->rows[$key][$column] ?? null;
    }
}
