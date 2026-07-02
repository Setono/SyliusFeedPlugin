<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Publish;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Publish\MinBytesGuardrail;

final class MinBytesGuardrailTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('min_bytes', (new MinBytesGuardrail())->getType());
    }

    /**
     * @test
     */
    public function it_trips_when_the_file_is_smaller_than_the_minimum(): void
    {
        self::assertTrue((new MinBytesGuardrail())->evaluate($this->result(511), null, ['bytes' => 512]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_file_meets_the_minimum(): void
    {
        $guardrail = new MinBytesGuardrail();

        self::assertFalse($guardrail->evaluate($this->result(512), null, ['bytes' => 512]));
        self::assertFalse($guardrail->evaluate($this->result(1024), null, ['bytes' => 512]));
    }

    private function result(int $bytes): FeedContextResult
    {
        $result = new FeedContextResult();
        $result->setBytes($bytes);

        return $result;
    }
}
