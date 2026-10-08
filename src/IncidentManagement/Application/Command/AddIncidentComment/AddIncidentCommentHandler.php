<?php

namespace OptimaRetail\IncidentManagement\Application\Command\AddIncidentComment;

use OptimaRetail\IncidentManagement\Domain\Incident\Exception\IncidentNotFound;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentId;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentRepository;
use OptimaRetail\Shared\Application\Transaction\TransactionManager;
use OptimaRetail\Shared\Domain\Clock;

final readonly class AddIncidentCommentHandler
{
    public function __construct(
        private IncidentRepository $incidents,
        private TransactionManager $tx,
        private Clock $clock,
    ) {}

    public function handle(AddIncidentComment $command): void
    {
        $id = IncidentId::fromString($command->incidentId);

        $this->tx->run(function () use ($id, $command) {
            $incident = $this->incidents->findForUpdate($id) ?? throw new IncidentNotFound($id);

            $incident->addComment($command->authorName, $command->body, $this->clock->now());
            $this->incidents->save($incident);
        });
    }
}
