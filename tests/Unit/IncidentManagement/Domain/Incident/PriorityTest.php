<?php

namespace Tests\Unit\IncidentManagement\Domain\Incident;

use OptimaRetail\IncidentManagement\Domain\Incident\Priority;
use PHPUnit\Framework\TestCase;

final class PriorityTest extends TestCase
{
    public function test_due_date_follows_sla(): void
    {
        $t = new \DateTimeImmutable('2026-01-01 10:00');
        $this->assertEquals(new \DateTimeImmutable('2026-01-01 14:00'), Priority::CRITICAL->dueFrom($t));
        $this->assertEquals(new \DateTimeImmutable('2026-01-08 10:00'), Priority::LOW->dueFrom($t));
    }

    public function test_weights_are_ordered(): void
    {
        $weights = array_map(fn (Priority $p) => $p->weight(), Priority::cases());
        $sorted = $weights;
        sort($sorted);
        $this->assertSame($sorted, $weights);
    }
}
