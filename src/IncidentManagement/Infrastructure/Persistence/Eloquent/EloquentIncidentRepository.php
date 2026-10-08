<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Str;
use OptimaRetail\IncidentManagement\Domain\Incident\Incident;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentId;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentRepository;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\IncidentCommentModel;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\IncidentLogModel;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\IncidentModel;

/** Adaptador Eloquent del puerto de escritura: traduce agregado ⇄ modelos. */
final class EloquentIncidentRepository implements IncidentRepository
{
    public function nextIdentity(): IncidentId
    {
        // UUIDv7: ordenable por tiempo (desempate cronológico en listados e historial).
        return IncidentId::fromString((string) Str::uuid7());
    }

    public function find(IncidentId $id): ?Incident
    {
        $model = IncidentModel::query()->find($id->value);

        return $model ? $this->toDomain($model) : null;
    }

    public function findForUpdate(IncidentId $id): ?Incident
    {
        $model = IncidentModel::query()->lockForUpdate()->find($id->value);

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Incident $incident): void
    {
        IncidentModel::query()->updateOrCreate(['id' => $incident->id()->value], [
            'title' => $incident->title(),
            'description' => $incident->description(),
            'status' => $incident->status(),
            'priority' => $incident->priority(),
            'requester_name' => $incident->requesterName(),
            'assigned_to' => $incident->assignedTo(),
            'created_at' => $incident->createdAt(),
            'updated_at' => $incident->updatedAt(),
        ]);

        foreach ($incident->pullPendingLogs() as $log) {
            IncidentLogModel::query()->create([
                'incident_id' => $log->incidentId->value,
                'action' => $log->action,
                'old_value' => $log->oldStatus?->value,
                'new_value' => $log->newStatus->value,
                'user_name' => $log->actor,
                'metadata' => $log->metadata ?: null,
                'created_at' => $log->occurredAt,
            ]);
        }

        foreach ($incident->pullPendingComments() as $comment) {
            IncidentCommentModel::query()->create([
                'incident_id' => $comment->incidentId->value,
                'author_name' => $comment->authorName,
                'body' => $comment->body,
                'created_at' => $comment->createdAt,
                'updated_at' => $comment->createdAt,
            ]);
        }
    }

    private function toDomain(IncidentModel $m): Incident
    {
        return Incident::reconstitute(
            id: IncidentId::fromString($m->id),
            title: $m->title,
            description: $m->description,
            status: $m->status,
            priority: $m->priority,
            requesterName: $m->requester_name,
            assignedTo: $m->assigned_to,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
