<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OptimaRetail\IncidentManagement\Application\Command\CreateIncident\CreateIncident;
use OptimaRetail\IncidentManagement\Domain\Incident\Incident;
use OptimaRetail\IncidentManagement\Domain\Incident\Priority;
use OptimaRetail\IncidentManagement\UI\Http\Request\Concerns\SpanishValidationMessages;
use OptimaRetail\IncidentManagement\UI\Labels\IncidentLabels;

/** Valida forma (tipos, longitudes); los invariantes de negocio los vuelve a garantizar el dominio. */
final class StoreIncidentRequest extends FormRequest
{
    use SpanishValidationMessages;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:'.Incident::TITLE_MIN_LENGTH, 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(Priority::values())],
            'requester_name' => ['required', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return IncidentLabels::fields('title', 'description', 'priority', 'requester_name', 'assigned_to');
    }

    public function toCommand(): CreateIncident
    {
        return new CreateIncident(
            title: $this->validated('title'),
            description: $this->validated('description'),
            priority: $this->validated('priority'),
            requesterName: $this->validated('requester_name'),
            assignedTo: $this->validated('assigned_to'),
            actor: $this->validated('requester_name'),
        );
    }
}
