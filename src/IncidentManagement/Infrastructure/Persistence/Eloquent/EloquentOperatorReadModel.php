<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent;

use OptimaRetail\IncidentManagement\Application\Query\OperatorReadModel;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\OperatorModel;

/** Adaptador de lectura del catálogo de operadores. */
final class EloquentOperatorReadModel implements OperatorReadModel
{
    public function list(): array
    {
        return OperatorModel::query()
            ->orderByDesc('active')
            ->orderBy('name')
            ->get()
            ->map(fn (OperatorModel $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'active' => $m->active,
                'created_at' => $m->created_at,
            ])
            ->all();
    }

    public function activeNames(): array
    {
        return OperatorModel::query()->where('active', true)->orderBy('name')->pluck('name')->all();
    }
}
