<?php

namespace OptimaRetail\Shared\Infrastructure\Time;

use OptimaRetail\Shared\Domain\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable;
    }
}
