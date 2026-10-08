<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use OptimaRetail\IncidentManagement\Application\Command\AddIncidentComment\AddIncidentComment;
use OptimaRetail\IncidentManagement\UI\Http\Request\Concerns\SpanishValidationMessages;
use OptimaRetail\IncidentManagement\UI\Labels\IncidentLabels;

final class StoreCommentRequest extends FormRequest
{
    use SpanishValidationMessages;

    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return IncidentLabels::fields('author_name', 'body');
    }

    public function toCommand(string $incidentId): AddIncidentComment
    {
        return new AddIncidentComment($incidentId, $this->validated('author_name'), $this->validated('body'));
    }
}
