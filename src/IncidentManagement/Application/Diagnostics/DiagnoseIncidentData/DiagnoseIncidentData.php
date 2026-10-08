<?php

namespace OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnoseIncidentData;

final readonly class DiagnoseIncidentData
{
    public function __construct(
        public bool $fix = false,
        public bool $acceptAssumptions = false,
        public string $actor = 'system:diagnostics',
    ) {}
}
