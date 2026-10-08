<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Request\Concerns;

/** Mensajes de validación en castellano, independientes del APP_LOCALE del entorno. */
trait SpanishValidationMessages
{
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede superar :max caracteres.',
            'in' => 'El valor de :attribute no es válido.',
        ];
    }
}
