<?php

namespace OptimaRetail\IncidentManagement\Application\Command\DeactivateOperator;

use OptimaRetail\IncidentManagement\Domain\Operator\Exception\OperatorNotFound;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorId;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorRepository;
use OptimaRetail\Shared\Application\Transaction\TransactionManager;

/** Baja lógica: el operador deja de salir en el selector pero la trazabilidad se conserva. */
final class DeactivateOperatorHandler
{
    public function __construct(
        private readonly OperatorRepository $operators,
        private readonly TransactionManager $tx,
    ) {}

    public function handle(DeactivateOperator $command): void
    {
        $id = OperatorId::fromString($command->operatorId);

        $this->tx->run(function () use ($id) {
            $operator = $this->operators->find($id) ?? throw new OperatorNotFound($id);
            $operator->deactivate();
            $this->operators->save($operator);
        });
    }
}
