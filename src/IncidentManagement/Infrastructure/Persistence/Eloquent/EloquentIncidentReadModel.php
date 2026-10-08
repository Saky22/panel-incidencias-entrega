<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use OptimaRetail\IncidentManagement\Application\Query\IncidentFilters;
use OptimaRetail\IncidentManagement\Application\Query\IncidentReadModel;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\IncidentCommentModel;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\IncidentLogModel;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\IncidentModel;
use OptimaRetail\Shared\Domain\Clock;

/** Adaptador de lectura: consultas para pantalla sin hidratar agregados; reglas delegadas en los enums del dominio. */
final class EloquentIncidentReadModel implements IncidentReadModel
{
    public function __construct(private readonly Clock $clock) {}

    public function search(IncidentFilters $filters, int $page, int $perPage = 20): array
    {
        $paginator = $this->filtered($filters)
            ->with('openReviewFlags')
            ->withCount('comments')
            ->orderByDesc('created_at')
            ->orderByDesc('id') // UUIDv7: desempate cronológico
            ->paginate($perPage, ['*'], 'page', max(1, $page));

        $now = $this->clock->now();

        return [
            'items' => array_map(fn (IncidentModel $m) => $this->view($m, $now), $paginator->items()),
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    public function countByStatus(IncidentFilters $filters): array
    {
        $counts = array_fill_keys(IncidentStatus::values(), 0);

        $this->filtered($filters)
            ->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->each(function ($row) use (&$counts) {
                $counts[$row->status] = (int) $row->total;
            });

        return $counts;
    }

    public function history(string $incidentId): ?array
    {
        $m = IncidentModel::query()->with(['logs', 'comments', 'openReviewFlags'])->withCount('comments')->find($incidentId);
        if (! $m) {
            return null;
        }

        $timeline = [
            ...$m->logs->map(fn ($l) => [
                'type' => 'log',
                'id' => $l->id,
                'occurred_at' => $l->created_at,
                'action' => $l->action->value,
                'old_value' => $l->old_value,
                'new_value' => $l->new_value,
                'actor' => $l->user_name,
                'metadata' => $l->metadata ?? [],
            ]),
            ...$m->comments->map(fn ($c) => [
                'type' => 'comment',
                'id' => $c->id,
                'occurred_at' => $c->created_at,
                'author_name' => $c->author_name,
                'body' => $c->body,
            ]),
        ];
        usort($timeline, fn ($a, $b) => [$a['occurred_at'], $a['id']] <=> [$b['occurred_at'], $b['id']]);

        return ['incident' => $this->view($m, $this->clock->now()), 'timeline' => $timeline];
    }

    public function knownAuthors(int $limit = self::KNOWN_AUTHORS_LIMIT): array
    {
        // Una consulta DISTINCT por columna (UNION elimina duplicados exactos): la base de datos
        // devuelve solo nombres distintos en vez de todas las filas de logs y comentarios.
        $names = IncidentModel::query()->toBase()->select('requester_name as name')
            ->union(IncidentModel::query()->toBase()->select('assigned_to as name')->whereNotNull('assigned_to'))
            ->union(IncidentLogModel::query()->toBase()->select('user_name as name'))
            ->union(IncidentCommentModel::query()->toBase()->select('author_name as name'));

        return IncidentModel::query()->getQuery()->newQuery()->fromSub($names, 'authors')
            ->whereRaw("TRIM(name) <> ''")
            ->orderBy('name')
            ->limit($limit)
            ->pluck('name')
            ->map(fn ($n) => trim((string) $n))
            ->all();
    }

    private function filtered(IncidentFilters $f): Builder
    {
        return IncidentModel::query()
            ->when($f->status, fn (Builder $q) => $q->where('status', $f->status->value))
            ->when($f->priority, fn (Builder $q) => $q->where('priority', $f->priority->value))
            ->when($f->search, function (Builder $q) use ($f) {
                $term = '%'.addcslashes($f->search, '%_\\').'%';
                $q->where(fn (Builder $w) => $w
                    ->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('requester_name', 'like', $term)
                    ->orWhere('assigned_to', 'like', $term));
            });
    }

    private function view(IncidentModel $m, \DateTimeImmutable $now): array
    {
        $status = $m->status;
        $dueAt = $m->priority->dueFrom($m->created_at);

        return [
            'id' => $m->id,
            'title' => $m->title,
            'description' => $m->description,
            'status' => $status->value,
            'priority' => $m->priority->value,
            'requester_name' => $m->requester_name,
            'assigned_to' => $m->assigned_to,
            'comments_count' => (int) ($m->comments_count ?? 0),
            'created_at' => $m->created_at,
            'updated_at' => $m->updated_at,
            'due_at' => $dueAt,
            'is_overdue' => ! $status->isFinal() && $dueAt < $now,
            'transitions' => array_map(fn (IncidentStatus $t) => [
                'status' => $t->value,
                'requires_reason' => $status->requiresReasonFor($t),
            ], $status->allowedTransitions()),
            'review_flags' => $m->openReviewFlags->map(fn ($f) => [
                'type' => $f->inconsistency_type,
                'created_at' => $f->created_at,
            ])->all(),
        ];
    }
}
