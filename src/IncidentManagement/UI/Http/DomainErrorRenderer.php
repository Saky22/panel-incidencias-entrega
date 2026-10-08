<?php

namespace OptimaRetail\IncidentManagement\UI\Http;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Validation\ValidationException;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\IncidentNotFound;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\InvalidIncidentData;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\InvalidStatusTransition;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\StatusChangeReasonRequired;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Operator\Exception\InvalidOperatorData;
use OptimaRetail\IncidentManagement\Domain\Operator\Exception\OperatorAlreadyRegistered;
use OptimaRetail\IncidentManagement\Domain\Operator\Exception\OperatorNotFound;
use OptimaRetail\IncidentManagement\UI\Labels\IncidentLabels as L;

/**
 * Traduce errores de dominio a respuestas HTTP: 404 o errores de validación por campo,
 * que Inertia pinta en el formulario sin código adicional.
 */
final class DomainErrorRenderer
{
    public static function register(ExceptionHandler $handler): void
    {
        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(fn (IncidentNotFound $e) => abort(404, 'La incidencia no existe.'));

        $handler->renderable(function (InvalidStatusTransition $e) {
            $allowed = implode(', ', array_map(fn (IncidentStatus $s) => '«'.L::status($s).'»', $e->from->allowedTransitions()));

            throw ValidationException::withMessages([
                'status' => sprintf('No se puede pasar de «%s» a «%s». Transiciones permitidas: %s.', L::status($e->from), L::status($e->to), $allowed),
            ]);
        });

        $handler->renderable(fn (StatusChangeReasonRequired $e) => throw ValidationException::withMessages([
            'reason' => sprintf('Pasar de «%s» a «%s» requiere indicar un motivo.', L::status($e->from), L::status($e->to)),
        ]));

        $handler->renderable(fn (InvalidIncidentData $e) => throw ValidationException::withMessages([
            $e->property => match ($e->rule) {
                InvalidIncidentData::TOO_SHORT => sprintf('El campo %s debe tener al menos %d caracteres.', L::field($e->property), $e->parameters['min']),
                default => sprintf('El campo %s es obligatorio.', L::field($e->property)),
            },
        ]));

        $handler->renderable(fn (OperatorNotFound $e) => abort(404, 'El operador no existe.'));

        $handler->renderable(fn (OperatorAlreadyRegistered $e) => throw ValidationException::withMessages([
            'name' => sprintf('«%s» ya está dado de alta como operador.', $e->name),
        ]));

        $handler->renderable(fn (InvalidOperatorData $e) => throw ValidationException::withMessages([
            'name' => match ($e->rule) {
                InvalidOperatorData::TOO_SHORT => sprintf('El campo %s debe tener al menos %d caracteres.', L::field($e->property), $e->parameters['min']),
                default => sprintf('El campo %s es obligatorio.', L::field($e->property)),
            },
        ]));
    }
}
