<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use OptimaRetail\IncidentManagement\Application\Command\RegisterOperator\RegisterOperator;
use OptimaRetail\IncidentManagement\Domain\Operator\Operator;
use OptimaRetail\IncidentManagement\UI\Http\Request\Concerns\SpanishValidationMessages;
use OptimaRetail\IncidentManagement\UI\Labels\IncidentLabels;

/** Valida forma; la unicidad/reactivación la decide el dominio en el handler. */
final class StoreOperatorRequest extends FormRequest
{
    use SpanishValidationMessages;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:'.Operator::NAME_MIN_LENGTH, 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return IncidentLabels::fields('name');
    }

    public function toCommand(): RegisterOperator
    {
        return new RegisterOperator($this->validated('name'));
    }
}
