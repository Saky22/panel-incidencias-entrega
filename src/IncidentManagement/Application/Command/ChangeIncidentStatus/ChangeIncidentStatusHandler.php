<?php

namespace OptimaRetail\IncidentManagement\Application\Command\ChangeIncidentStatus;

use OptimaRetail\IncidentManagement\Domain\Incident\Exception\IncidentNotFound;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentId;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentRepository;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\Shared\Application\Transaction\TransactionManager;
use OptimaRetail\Shared\Domain\Clock;

final readonly class ChangeIncidentStatusHandler
{
    public function __construct(
        private IncidentRepository $incidents,
        private TransactionManager $tx,
        private Clock $clock,
    ) {}

    public function handle(ChangeIncidentStatus $command): IncidentStatus
    {
        $id = IncidentId::fromString($command->incidentId);
        $target = IncidentStatus::from($command->status);

        return $this->tx->run(function () use ($id, $target, $command) {
            // Bloqueo pesimista: dos operadores no validan contra el mismo estado viejo.
            $incident = $this->incidents->findForUpdate($id) ?? throw new IncidentNotFound($id);

            $incident->changeStatus($target, $command->actor, $command->reason, $command->context, $this->clock->now());
            $this->incidents->save($incident);

            return $incident->status();
        });
    }
}
