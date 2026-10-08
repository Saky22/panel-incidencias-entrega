<?php

namespace Tests\Support;

use OptimaRetail\Shared\Domain\Clock;

final class FrozenClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now = new \DateTimeImmutable('2026-10-08 10:00:00')) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }
}
