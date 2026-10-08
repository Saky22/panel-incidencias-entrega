<?php

namespace OptimaRetail\IncidentManagement\Application\Diagnostics;

interface InconsistencyDetector
{
    /** @return list<Inconsistency> */
    public function detect(): array;
}
