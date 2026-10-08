<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OptimaRetail\IncidentManagement\Application\Command\ChangeIncidentStatus\ChangeIncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\UI\Http\Request\Concerns\SpanishValidationMessages;
use OptimaRetail\IncidentManagement\UI\Labels\IncidentLabels;

/** Valida forma; la regla de transición la decide el dominio. */
final class ChangeIncidentStatusRequest extends FormRequest
{
    use SpanishValidationMessages;

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(IncidentStatus::values())],
            'user_name' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return IncidentLabels::fields('status', 'user_name', 'reason');
    }

    public function toCommand(string $incidentId): ChangeIncidentStatus
    {
        return new ChangeIncidentStatus(
            incidentId: $incidentId,
            status: $this->validated('status'),
            actor: $this->validated('user_name'),
            reason: $this->validated('reason'),
            context: ['ip' => $this->ip(), 'source' => 'web'],
        );
    }
}
