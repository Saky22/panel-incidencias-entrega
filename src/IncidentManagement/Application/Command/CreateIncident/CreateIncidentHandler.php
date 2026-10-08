<?php

namespace OptimaRetail\IncidentManagement\Application\Command\CreateIncident;

use OptimaRetail\IncidentManagement\Domain\Incident\Incident;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentId;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentRepository;
use OptimaRetail\IncidentManagement\Domain\Incident\Priority;
use OptimaRetail\Shared\Application\Transaction\TransactionManager;
use OptimaRetail\Shared\Domain\Clock;

final readonly class CreateIncidentHandler
{
    public function __construct(
        private IncidentRepository $incidents,
        private TransactionManager $tx,
        private Clock $clock,
    ) {}

    public function handle(CreateIncident $command): IncidentId
    {
        $incident = Incident::open(
            id: $this->incidents->nextIdentity(),
            title: $command->title,
            description: $command->description,
            priority: Priority::from($command->priority),
            requesterName: $command->requesterName,
            assignedTo: $command->assignedTo,
            actor: $command->actor,
            at: $this->clock->now(),
        );

        // Incidencia + asiento de creación: o se guardan los dos o ninguno.
        $this->tx->run(fn () => $this->incidents->save($incident));

        return $incident->id();
    }
}
