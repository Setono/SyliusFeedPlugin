<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * A standalone, admin-managed enrichment dataset (§10.1): a key => row store that resolvers join
 * against per item. Rows are imported from a source (csv/url) and refreshed with a keep-last-good
 * policy, so a failed refresh never blanks the currently-serving data.
 */
interface LookupTableInterface extends ResourceInterface, CodeAwareInterface
{
    public const SOURCE_TYPE_CSV = 'csv';

    public const SOURCE_TYPE_URL = 'url';

    public const REFRESH_POLICY_MANUAL = 'manual';

    public const REFRESH_POLICY_BEFORE_GENERATE = 'before_generate';

    public const REFRESH_POLICY_SCHEDULED = 'scheduled';

    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(?string $name): void;

    /**
     * The lookup source type importing the rows, e.g. `csv` or `url`.
     */
    public function getSourceType(): ?string;

    public function setSourceType(?string $sourceType): void;

    /**
     * Source-specific configuration, e.g. `['path' => '...']` (csv) or `['url' => '...']` (url).
     *
     * @return array<string, mixed>
     */
    public function getSourceConfig(): array;

    /**
     * @param array<string, mixed> $sourceConfig
     */
    public function setSourceConfig(array $sourceConfig): void;

    /**
     * The source column whose value is the row's join key.
     */
    public function getKeyColumn(): ?string;

    public function setKeyColumn(?string $keyColumn): void;

    /**
     * The item source-field whose value supplies the join key when resolving `lookup:{code}:{column}`.
     */
    public function getJoinField(): ?string;

    public function setJoinField(?string $joinField): void;

    public function getRefreshPolicy(): string;

    public function setRefreshPolicy(string $refreshPolicy): void;

    public function getRefreshedAt(): ?\DateTimeInterface;

    public function setRefreshedAt(?\DateTimeInterface $refreshedAt): void;

    /**
     * The imported keyed store: join key => (column => value).
     *
     * @return array<string, array<string, scalar|null>>
     */
    public function getRows(): array;

    /**
     * @param array<string, array<string, scalar|null>> $rows
     */
    public function setRows(array $rows): void;

    /**
     * The stored row for the given join key, or null on a miss.
     *
     * @return array<string, scalar|null>|null
     */
    public function getRow(string $key): ?array;

    /**
     * The stored value at the given join key and column, or null on a miss.
     *
     * @return scalar|null
     */
    public function getColumn(string $key, string $column): mixed;
}
