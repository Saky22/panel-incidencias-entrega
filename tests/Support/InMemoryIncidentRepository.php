<?php

namespace Tests\Support;

use OptimaRetail\IncidentManagement\Domain\Incident\Incident;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentComment;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentId;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentLog;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentRepository;

/** Adaptador en memoria del puerto: permite testear casos de uso sin BD ni framework. */
final class InMemoryIncidentRepository implements IncidentRepository
{
    /** @var array<string, Incident> */
    private array $incidents = [];

    /** @var list<IncidentLog> */
    public array $logs = [];

    /** @var list<IncidentComment> */
    public array $comments = [];

    private int $sequence = 0;

    public function nextIdentity(): IncidentId
    {
        return IncidentId::fromString(sprintf('01a11a71-0000-7000-8000-%012d', ++$this->sequence));
    }

    public function find(IncidentId $id): ?Incident
    {
        return $this->incidents[$id->value] ?? null;
    }

    public function findForUpdate(IncidentId $id): ?Incident
    {
        return $this->find($id);
    }

    public function save(Incident $incident): void
    {
        $this->incidents[$incident->id()->value] = $incident;
        array_push($this->logs, ...$incident->pullPendingLogs());
        array_push($this->comments, ...$incident->pullPendingComments());
    }
}
