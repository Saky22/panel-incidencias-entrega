<?php

namespace Tests\Unit\IncidentManagement\Domain\Incident;

use OptimaRetail\IncidentManagement\Domain\Incident\IncidentId;
use PHPUnit\Framework\TestCase;

final class IncidentIdTest extends TestCase
{
    public function test_normalises_and_compares_by_value(): void
    {
        $a = IncidentId::fromString(' 01A11A71-0000-7000-8000-000000000001 ');
        $this->assertSame('01a11a71-0000-7000-8000-000000000001', (string) $a);
        $this->assertTrue($a->equals(IncidentId::fromString('01a11a71-0000-7000-8000-000000000001')));
    }

    public function test_rejects_invalid_uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        IncidentId::fromString('id-1');
    }
}
