<?php

namespace OptimaRetail\IncidentManagement\Application\Query;

/**
 * Puerto de lectura de operadores: gestión del catálogo y nombres activos
 * para el selector de autor.
 */
interface OperatorReadModel
{
    /**
     * @return list<array{id: string, name: string, active: bool, created_at: \DateTimeImmutable}>
     */
    public function list(): array;

    /** @return list<string> nombres de los operadores activos, ordenados */
    public function activeNames(): array;
}
