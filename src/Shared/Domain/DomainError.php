<?php

namespace OptimaRetail\Shared\Domain;

/**
 * Raíz de los errores de negocio de cualquier bounded context.
 * Llevan datos estructurados; el texto para el usuario lo compone la capa UI.
 */
abstract class DomainError extends \DomainException
{
    /** Código estable del error (para mapeo en UI, logs y tests). */
    abstract public function errorCode(): string;
}
