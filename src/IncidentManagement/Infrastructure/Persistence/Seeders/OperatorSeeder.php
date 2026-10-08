<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use OptimaRetail\IncidentManagement\Domain\Operator\Operator;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorRepository;
use OptimaRetail\Shared\Domain\Clock;

/** Operadores de ejemplo: coinciden con los asignados de las incidencias de muestra. */
final class OperatorSeeder extends Seeder
{
    public function run(OperatorRepository $repo, Clock $clock): void
    {
        foreach (['Marta Soler', 'Jordi Puig', 'Laura Vidal'] as $name) {
            if (! $repo->findByName($name)) {
                $repo->save(Operator::register($repo->nextIdentity(), $name, $clock->now()));
            }
        }
    }
}
