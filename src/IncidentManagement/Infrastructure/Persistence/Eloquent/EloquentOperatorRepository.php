<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use OptimaRetail\IncidentManagement\Domain\Operator\Exception\OperatorAlreadyRegistered;
use OptimaRetail\IncidentManagement\Domain\Operator\Operator;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorId;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorRepository;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\OperatorModel;

/** Adaptador Eloquent del puerto de escritura de operadores. */
final class EloquentOperatorRepository implements OperatorRepository
{
    public function nextIdentity(): OperatorId
    {
        return OperatorId::fromString((string) Str::uuid7());
    }

    public function find(OperatorId $id): ?Operator
    {
        $model = OperatorModel::query()->find($id->value);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByName(string $name): ?Operator
    {
        // LOWER() explícito: en SQLite «=» distingue mayúsculas y en MySQL no.
        $model = OperatorModel::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Operator $operator): void
    {
        try {
            OperatorModel::query()->updateOrCreate(['id' => $operator->id()->value], [
                'name' => $operator->name(),
                'active' => $operator->isActive(),
                'created_at' => $operator->createdAt(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dos altas simultáneas del mismo nombre: la segunda choca con el índice único.
            // Se traduce al error de dominio (validación en la UI) en vez de un 500.
            throw new OperatorAlreadyRegistered($operator->name());
        }
    }

    private function toDomain(OperatorModel $m): Operator
    {
        return Operator::reconstitute(
            id: OperatorId::fromString($m->id),
            name: $m->name,
            active: $m->active,
            createdAt: $m->created_at,
        );
    }
}
