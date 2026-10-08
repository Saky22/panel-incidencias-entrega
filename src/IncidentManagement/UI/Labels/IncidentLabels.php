<?php

namespace OptimaRetail\IncidentManagement\UI\Labels;

use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Incident\LogAction;
use OptimaRetail\IncidentManagement\Domain\Incident\Priority;

/**
 * Vocabulario visible (es-ES). La presentación vive en la UI, no en los enums de dominio:
 * cambiar un texto o añadir un idioma no toca el dominio.
 */
final class IncidentLabels
{
    public static function status(IncidentStatus|string|null $status): ?string
    {
        $status = is_string($status) ? IncidentStatus::tryFrom($status) : $status;

        return match ($status) {
            IncidentStatus::OPEN => 'Abierta',
            IncidentStatus::UNDER_REVIEW => 'En revisión',
            IncidentStatus::BLOCKED => 'Bloqueada',
            IncidentStatus::RESOLVED => 'Resuelta',
            null => null,
        };
    }

    public static function priority(Priority|string $priority): string
    {
        return match (is_string($priority) ? Priority::from($priority) : $priority) {
            Priority::LOW => 'Baja',
            Priority::MEDIUM => 'Media',
            Priority::HIGH => 'Alta',
            Priority::CRITICAL => 'Crítica',
        };
    }

    public static function logAction(LogAction|string $action): string
    {
        return match (is_string($action) ? LogAction::from($action) : $action) {
            LogAction::CREATED => 'Creación',
            LogAction::STATUS_CHANGED => 'Cambio de estado',
            LogAction::REOPENED => 'Reapertura',
            LogAction::UNBLOCKED => 'Desbloqueo',
            LogAction::RECONCILED => 'Reconciliación',
        };
    }

    public static function field(string $property): string
    {
        return [
            'title' => 'título',
            'description' => 'descripción',
            'priority' => 'prioridad',
            'requester_name' => 'solicitante',
            'assigned_to' => 'asignado a',
            'status' => 'estado',
            'user_name' => 'operador',
            'reason' => 'motivo',
            'author_name' => 'autor',
            'body' => 'comentario',
            'name' => 'nombre',
        ][$property] ?? $property;
    }

    /** @return array<string,string> */
    public static function fields(string ...$properties): array
    {
        return array_combine($properties, array_map(self::field(...), $properties));
    }
}
