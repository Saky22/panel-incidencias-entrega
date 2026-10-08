<?php

namespace Tests\Support;

use OptimaRetail\IncidentManagement\Domain\Operator\Operator;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorId;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorRepository;

/** Adaptador en memoria del puerto: permite testear casos de uso sin BD ni framework. */
final class InMemoryOperatorRepository implements OperatorRepository
{
    /** @var array<string, Operator> */
    private array $operators = [];

    private int $sequence = 0;

    public function nextIdentity(): OperatorId
    {
        return OperatorId::fromString(sprintf('02b22b72-0000-7000-8000-%012d', ++$this->sequence));
    }

    public function find(OperatorId $id): ?Operator
    {
        return $this->operators[$id->value] ?? null;
    }

    public function findByName(string $name): ?Operator
    {
        $name = mb_strtolower(trim($name));
        foreach ($this->operators as $operator) {
            if (mb_strtolower($operator->name()) === $name) {
                return $operator;
            }
        }

        return null;
    }

    public function save(Operator $operator): void
    {
        $this->operators[$operator->id()->value] = $operator;
    }
}
