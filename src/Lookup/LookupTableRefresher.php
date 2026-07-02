<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Model\LookupTableInterface;

/**
 * Imports a LookupTable's rows via the source registered for its `sourceType`, keying each row by
 * its `keyColumn`. On success it stamps `refreshedAt`; on any failure it keeps the last-good rows
 * and `refreshedAt`, logging a warning — a broken source must not blank out the serving data (§10.1).
 */
final class LookupTableRefresher implements LookupTableRefresherInterface
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly LookupSourceRegistryInterface $sourceRegistry,
        private readonly LoggerInterface $logger,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function refresh(LookupTableInterface $table): void
    {
        $sourceType = $table->getSourceType();
        $keyColumn = $table->getKeyColumn();

        if (null === $sourceType || null === $keyColumn || !$this->sourceRegistry->has($sourceType)) {
            $this->logger->warning('Cannot refresh lookup table: missing source type/key column or unknown source', [
                'code' => $table->getCode(),
            ]);

            return;
        }

        try {
            $rows = [];
            foreach ($this->sourceRegistry->get($sourceType)->fetch($table->getSourceConfig()) as $row) {
                $key = $row[$keyColumn] ?? null;
                if (null === $key) {
                    continue;
                }

                $rows[(string) $key] = $row;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Lookup table refresh failed; keeping the last-good rows', [
                'code' => $table->getCode(),
                'exception' => $e,
            ]);

            return;
        }

        $table->setRows($rows);
        $table->setRefreshedAt(new \DateTimeImmutable());
        $this->getManager($table)->flush();
    }
}
