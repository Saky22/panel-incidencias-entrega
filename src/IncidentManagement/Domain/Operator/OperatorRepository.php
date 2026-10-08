<?php

namespace OptimaRetail\IncidentManagement\Domain\Operator;

/** Puerto de escritura de operadores (catálogo interno, sin lecturas de pantalla). */
interface OperatorRepository
{
    public function nextIdentity(): OperatorId;

    public function find(OperatorId $id): ?Operator;

    /** Búsqueda por nombre, insensible a mayúsculas y a espacios laterales. */
    public function findByName(string $name): ?Operator;

    public function save(Operator $operator): void;
}
