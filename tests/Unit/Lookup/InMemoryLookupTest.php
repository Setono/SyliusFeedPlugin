<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Lookup;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;

final class InMemoryLookupTest extends TestCase
{
    private InMemoryLookup $lookup;

    protected function setUp(): void
    {
        $this->lookup = new InMemoryLookup();
    }

    /**
     * @param array<string, mixed> $columns
     *
     * @return array<string, array<string, mixed>>
     */
    private function row(string $key, array $columns): array
    {
        return [$key => $columns];
    }

    /**
     * @test
     */
    public function it_returns_the_cell_for_a_known_table_key_and_column(): void
    {
        $this->lookup->addTable('badges', [
            'SKU-1' => ['suffix' => 'Bestseller', 'color' => 'gold'],
        ]);

        self::assertSame('Bestseller', $this->lookup->get('badges', 'SKU-1', 'suffix'));
        self::assertSame('gold', $this->lookup->get('badges', 'SKU-1', 'color'));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_table_is_unknown(): void
    {
        self::assertNull($this->lookup->get('missing', 'SKU-1', 'suffix'));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_key_is_unknown(): void
    {
        $this->lookup->addTable('badges', ['SKU-1' => ['suffix' => 'Bestseller']]);

        self::assertNull($this->lookup->get('badges', 'SKU-2', 'suffix'));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_column_is_unknown(): void
    {
        $this->lookup->addTable('badges', ['SKU-1' => ['suffix' => 'Bestseller']]);

        self::assertNull($this->lookup->get('badges', 'SKU-1', 'unknown_column'));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_key_is_not_scalar(): void
    {
        $this->lookup->addTable('badges', ['SKU-1' => ['suffix' => 'Bestseller']]);

        self::assertNull($this->lookup->get('badges', ['SKU-1'], 'suffix'));
        self::assertNull($this->lookup->get('badges', new \stdClass(), 'suffix'));
        self::assertNull($this->lookup->get('badges', null, 'suffix'));
    }

    /**
     * @test
     */
    public function it_coerces_scalar_keys_to_string(): void
    {
        // The row is keyed by a numeric string. PHP itself normalizes such array keys to int,
        // so building the array through a non-literal (variable) key keeps its static type as
        // "string" for PHPStan while still exercising the very same runtime normalization that
        // InMemoryLookup::get()'s (string) cast has to reconcile.
        $this->lookup->addTable('badges', $this->row('1', ['suffix' => 'Bestseller']));

        self::assertSame('Bestseller', $this->lookup->get('badges', 1, 'suffix'));
        self::assertSame('Bestseller', $this->lookup->get('badges', 1.0, 'suffix'));
    }

    /**
     * @test
     */
    public function it_overwrites_a_previously_added_table_with_the_same_code(): void
    {
        $this->lookup->addTable('badges', ['SKU-1' => ['suffix' => 'Old']]);
        $this->lookup->addTable('badges', ['SKU-1' => ['suffix' => 'New']]);

        self::assertSame('New', $this->lookup->get('badges', 'SKU-1', 'suffix'));
    }
}
