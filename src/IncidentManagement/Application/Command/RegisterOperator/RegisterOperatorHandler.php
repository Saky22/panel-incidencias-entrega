<?php

namespace OptimaRetail\IncidentManagement\Application\Command\RegisterOperator;

use OptimaRetail\IncidentManagement\Domain\Operator\Exception\OperatorAlreadyRegistered;
use OptimaRetail\IncidentManagement\Domain\Operator\Operator;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorId;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorRepository;
use OptimaRetail\Shared\Application\Transaction\TransactionManager;
use OptimaRetail\Shared\Domain\Clock;

final class RegisterOperatorHandler
{
    public function __construct(
        private readonly OperatorRepository $operators,
        private readonly TransactionManager $tx,
        private readonly Clock $clock,
    ) {}

    /**
     * Alta de operador. Dar de alta un nombre que existe pero está de baja
     * lo reactiva; si ya está activo es un error.
     */
    public function handle(RegisterOperator $command): OperatorId
    {
        return $this->tx->run(function () use ($command) {
            $existing = $this->operators->findByName(trim($command->name));

            if ($existing) {
                if ($existing->isActive()) {
                    throw new OperatorAlreadyRegistered($existing->name());
                }
                $existing->reactivate();
                $this->operators->save($existing);

                return $existing->id();
            }

            $operator = Operator::register($this->operators->nextIdentity(), $command->name, $this->clock->now());
            $this->operators->save($operator);

            return $operator->id();
        });
    }
}
