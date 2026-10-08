<?php

namespace Tests\Unit\IncidentManagement\Domain\Incident;

use OptimaRetail\IncidentManagement\Domain\Incident\Exception\InvalidIncidentData;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\InvalidStatusTransition;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\StatusChangeReasonRequired;
use OptimaRetail\IncidentManagement\Domain\Incident\Incident;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentId;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Incident\LogAction;
use OptimaRetail\IncidentManagement\Domain\Incident\Priority;
use PHPUnit\Framework\TestCase;

final class IncidentTest extends TestCase
{
    private const ID = '01a11a71-0000-7000-8000-000000000001';

    private \DateTimeImmutable $t0;

    protected function setUp(): void
    {
        $this->t0 = new \DateTimeImmutable('2026-01-01 10:00');
    }

    private function make(Priority $p = Priority::HIGH, string $title = 'Caja 3 no imprime', string $description = 'La impresora de tickets falla'): Incident
    {
        return Incident::open(IncidentId::fromString(self::ID), $title, $description, $p, 'Tienda 12', null, 'ana', $this->t0);
    }

    private function change(Incident $i, IncidentStatus $to, ?string $reason = null, string $actor = 'luis', array $ctx = []): void
    {
        $i->changeStatus($to, $actor, $reason, $ctx, $this->t0->modify('+1 hour'));
    }

    public function test_new_incident_is_open_and_logs_creation(): void
    {
        $i = $this->make();
        $this->assertSame(IncidentStatus::OPEN, $i->status());
        $this->assertTrue($i->id()->equals(IncidentId::fromString(self::ID)));

        $logs = $i->pullPendingLogs();
        $this->assertCount(1, $logs);
        $this->assertSame(LogAction::CREATED, $logs[0]->action);
        $this->assertNull($logs[0]->oldStatus);
        $this->assertSame(IncidentStatus::OPEN, $logs[0]->newStatus);
        $this->assertEquals($this->t0, $logs[0]->occurredAt);
        $this->assertSame([], $i->pullPendingLogs(), 'pull vacía la cola');
    }

    public function test_validates_title_length(): void
    {
        try {
            $this->make(title: '  abc ');
            $this->fail('Debió lanzar');
        } catch (InvalidIncidentData $e) {
            $this->assertSame(['title', InvalidIncidentData::TOO_SHORT, ['min' => 5]], [$e->property, $e->rule, $e->parameters]);
        }
    }

    public function test_validates_required_fields(): void
    {
        try {
            $this->make(description: '   ');
            $this->fail('Debió lanzar');
        } catch (InvalidIncidentData $e) {
            $this->assertSame(['description', InvalidIncidentData::REQUIRED], [$e->property, $e->rule]);
        }
    }

    public function test_valid_status_change_is_logged_with_reason_and_context(): void
    {
        $i = $this->make();
        $i->pullPendingLogs();
        $this->change($i, IncidentStatus::UNDER_REVIEW);
        $this->change($i, IncidentStatus::BLOCKED, 'Esperando proveedor', ctx: ['ip' => '10.0.0.1']);

        $logs = $i->pullPendingLogs();
        $this->assertCount(2, $logs);
        $this->assertSame([IncidentStatus::OPEN, IncidentStatus::UNDER_REVIEW], [$logs[0]->oldStatus, $logs[0]->newStatus]);
        $this->assertSame([IncidentStatus::UNDER_REVIEW, IncidentStatus::BLOCKED], [$logs[1]->oldStatus, $logs[1]->newStatus]);
        $this->assertSame(['ip' => '10.0.0.1', 'reason' => 'Esperando proveedor'], $logs[1]->metadata);
        $this->assertSame(IncidentStatus::BLOCKED, $i->status());
    }

    public function test_invalid_transition_throws_and_does_not_mutate(): void
    {
        $i = $this->make();
        $i->pullPendingLogs();
        try {
            $this->change($i, IncidentStatus::RESOLVED);
            $this->fail('Debió lanzar');
        } catch (InvalidStatusTransition $e) {
            $this->assertSame([IncidentStatus::OPEN, IncidentStatus::RESOLVED], [$e->from, $e->to]);
        }
        $this->assertSame(IncidentStatus::OPEN, $i->status());
        $this->assertSame([], $i->pullPendingLogs());
    }

    public function test_blocking_without_reason_throws(): void
    {
        $i = $this->make();
        $this->change($i, IncidentStatus::UNDER_REVIEW);
        $this->expectException(StatusChangeReasonRequired::class);
        $this->change($i, IncidentStatus::BLOCKED, '  ');
    }

    public function test_reopen_requires_reason_and_leaves_explicit_reopen_log(): void
    {
        $i = $this->make();
        $this->change($i, IncidentStatus::UNDER_REVIEW);
        $this->change($i, IncidentStatus::RESOLVED);
        try {
            $this->change($i, IncidentStatus::OPEN);
            $this->fail('Reabrir sin motivo debe fallar');
        } catch (StatusChangeReasonRequired) {
        }
        $i->pullPendingLogs();

        $this->change($i, IncidentStatus::OPEN, 'El cliente reporta que sigue fallando');

        $log = $i->pullPendingLogs()[0];
        $this->assertSame(IncidentStatus::OPEN, $i->status());
        $this->assertSame(LogAction::REOPENED, $log->action);
        $this->assertSame('El cliente reporta que sigue fallando', $log->metadata['reason']);
    }

    public function test_unblock_leaves_explicit_unblock_log(): void
    {
        $i = $this->make();
        $this->change($i, IncidentStatus::UNDER_REVIEW);
        $this->change($i, IncidentStatus::BLOCKED, 'Proveedor');
        $i->pullPendingLogs();
        $this->change($i, IncidentStatus::UNDER_REVIEW, 'Repuesto recibido');

        $this->assertSame(LogAction::UNBLOCKED, $i->pullPendingLogs()[0]->action);
    }

    public function test_actor_is_required(): void
    {
        $this->expectException(InvalidIncidentData::class);
        $this->change($this->make(), IncidentStatus::UNDER_REVIEW, actor: ' ');
    }

    public function test_overdue_by_priority_sla(): void
    {
        $i = $this->make(Priority::CRITICAL); // SLA 4 h
        $this->assertEquals($this->t0->modify('+4 hours'), $i->dueAt());
        $this->assertFalse($i->isOverdue(new \DateTimeImmutable('2026-01-01 13:59')));
        $this->assertTrue($i->isOverdue(new \DateTimeImmutable('2026-01-01 14:01')));

        $this->change($i, IncidentStatus::UNDER_REVIEW);
        $this->change($i, IncidentStatus::RESOLVED);
        $this->assertFalse($i->isOverdue(new \DateTimeImmutable('2027-01-01')), 'resuelta nunca vence');
    }

    public function test_add_comment(): void
    {
        $i = $this->make();
        $c = $i->addComment(' ana ', ' Revisado cableado ', $this->t0);
        $this->assertSame(['ana', 'Revisado cableado'], [$c->authorName, $c->body]);
        $this->assertSame([$c], $i->pullPendingComments());

        $this->expectException(InvalidIncidentData::class);
        $i->addComment('ana', '   ', $this->t0);
    }
}
