<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders\IncidentSeeder;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders\InconsistentDataSeeder;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders\OperatorSeeder;

/** Punto de entrada de `db:seed`: delega en los seeders de cada bounded context. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OperatorSeeder::class,
            IncidentSeeder::class,
            InconsistentDataSeeder::class,
        ]);
    }
}
