<?php

use App\Providers\AppServiceProvider;
use OptimaRetail\IncidentManagement\IncidentManagementServiceProvider;
use OptimaRetail\Shared\SharedServiceProvider;

return [
    AppServiceProvider::class,
    // Bounded contexts (src/): cada uno es su propio composition root.
    SharedServiceProvider::class,
    IncidentManagementServiceProvider::class,
];
