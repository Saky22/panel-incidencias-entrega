<?php

namespace Tests\Unit\IncidentManagement\Application\Command;

use OptimaRetail\IncidentManagement\Application\Command\AddIncidentComment\AddIncidentComment;
use OptimaRetail\IncidentManagement\Application\Command\AddIncidentComment\AddIncidentCommentHandler;
use OptimaRetail\IncidentManagement\Application\Command\ChangeIncidentStatus\ChangeIncidentStatus;
use OptimaRetail\IncidentManagement\Application\Command\ChangeIncidentStatus\ChangeIncidentStatusHandler;
use OptimaRetail\IncidentManagement\Application\Command\CreateIncident\CreateIncident;
use OptimaRetail\IncidentManagement\Application\Command\CreateIncident\CreateIncidentHandler;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\IncidentNotFound;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\InvalidStatusTransition;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentId;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Incident\LogAction;
use PHPUnit\Framework\TestCase;
use Tests\Support\FrozenClock;
use Tests\Support\ImmediateTransactionManager;
use Tests\Support\InMemoryIncidentRepository;

/** Casos de uso aislados: puertos sustituidos por adaptadores en memoria. */
final class IncidentCommandHandlersTest extends TestCase
{
    private InMemoryIncidentRepository $repo;

    private ImmediateTransactionManager $tx;

    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->repo = new InMemoryIncidentRepository;
        $this->tx = new ImmediateTransactionManager;
        $this->clock = new FrozenClock;
    }

    private function create(): IncidentId
    {
        return (new CreateIncidentHandler($this->repo, $this->tx, $this->clock))
            ->handle(new CreateIncident('TPV caja 2 bloqueado', 'No arranca', 'high', 'Tienda Sants', null, 'ana'));
    }

    private function changeStatus(IncidentId $id, string $status): IncidentStatus
    {
        return (new ChangeIncidentStatusHandler($this->repo, $this->tx, $this->clock))
            ->handle(new ChangeIncidentStatus($id->value, $status, 'luis', null, ['ip' => '127.0.0.1']));
    }

    public function test_create_persists_incident_and_creation_log_in_one_transaction(): void
    {
        $id = $this->create();

        $this->assertSame(IncidentStatus::OPEN, $this->repo->find($id)->status());
        $this->assertCount(1, $this->repo->logs);
        $this->assertSame(LogAction::CREATED, $this->repo->logs[0]->action);
        $this->assertEquals($this->clock->now(), $this->repo->logs[0]->occurredAt, 'el tiempo viene del Clock');
        $this->assertSame(1, $this->tx->runs);
    }

    public function test_change_status_uses_clock_and_records_context(): void
    {
        $id = $this->create();
        $this->clock->advance('+30 minutes');

        $this->assertSame(IncidentStatus::UNDER_REVIEW, $this->changeStatus($id, 'under_review'));

        $log = end($this->repo->logs);
        $this->assertSame(['ip' => '127.0.0.1'], $log->metadata);
        $this->assertEquals(new \DateTimeImmutable('2026-10-08 10:30:00'), $log->occurredAt);
    }

    public function test_invalid_transition_propagates_domain_error(): void
    {
        $id = $this->create();
        $this->expectException(InvalidStatusTransition::class);
        $this->changeStatus($id, 'resolved');
    }

    public function test_unknown_incident(): void
    {
        $this->expectException(IncidentNotFound::class);
        $this->changeStatus(IncidentId::fromString('01a11a71-0000-7000-8000-999999999999'), 'under_review');
    }

    public function test_add_comment(): void
    {
        $id = $this->create();
        (new AddIncidentCommentHandler($this->repo, $this->tx, $this->clock))
            ->handle(new AddIncidentComment($id->value, 'luis', 'Reinicio en remoto'));

        $this->assertCount(1, $this->repo->comments);
        $this->assertSame('Reinicio en remoto', $this->repo->comments[0]->body);
    }
}
