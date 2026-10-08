<?php

namespace OptimaRetail\IncidentManagement\Application\Diagnostics;

interface InconsistencyRepairer
{
    /**
     * Aplica la corrección (dentro de la transacción del llamante), revalidando antes
     * que los datos no han cambiado desde la detección.
     *
     * @return array<string, mixed> qué se hizo (ids creados/borrados, copia de seguridad...)
     */
    public function repair(Inconsistency $inconsistency, string $actor, \DateTimeImmutable $at): array;

    /**
     * Marca la incidencia para revisión manual sin modificar sus datos. Idempotente.
     *
     * @return bool true si se creó la marca; false si ya existía una abierta
     */
    public function flagForReview(Inconsistency $inconsistency, string $actor, \DateTimeImmutable $at): bool;

    /**
     * Cierra las marcas abiertas cuya inconsistencia ya no se detecta.
     *
     * @param  list<Inconsistency>  $stillPending  hallazgos que siguen necesitando revisión
     * @return int marcas cerradas
     */
    public function closeResolvedFlags(array $stillPending, \DateTimeImmutable $at): int;
}
