<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Presenter;

use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Incident\LogAction;
use OptimaRetail\IncidentManagement\Domain\Incident\Priority;
use OptimaRetail\IncidentManagement\UI\Labels\DiagnosisLabels;
use OptimaRetail\IncidentManagement\UI\Labels\IncidentLabels as L;

/** Convierte vistas de aplicación en el contrato JSON que consume React. */
final class IncidentPresenter
{
    private const int EXCERPT_LENGTH = 140;

    /** @param array{items: list<array>, page: int, last_page: int, per_page: int, total: int} $result */
    public function list(array $result): array
    {
        return [
            'data' => array_map($this->summary(...), $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        ];
    }

    public function history(array $history): array
    {
        return [
            'incident' => $this->summary($history['incident']) + ['description' => $history['incident']['description']],
            'timeline' => array_map($this->timelineEntry(...), $history['timeline']),
            'stats' => [
                'comments' => count(array_filter($history['timeline'], fn ($e) => $e['type'] === 'comment')),
                'status_changes' => count(array_filter($history['timeline'], fn ($e) => LogAction::tryFrom($e['action'] ?? '')?->isTransition() ?? false)),
            ],
        ];
    }

    public function options(): array
    {
        return [
            'statuses' => array_map(fn (IncidentStatus $s) => ['value' => $s->value, 'label' => L::status($s)], IncidentStatus::cases()),
            'priorities' => array_map(fn (Priority $p) => ['value' => $p->value, 'label' => L::priority($p), 'sla_hours' => $p->slaHours()], Priority::cases()),
        ];
    }

    /**
     * Lista del selector de autor: primero los operadores activos y después los
     * nombres históricos que no duplican (insensible a mayúsculas). Sin
     * autenticación no se puede cerrar la lista: siempre admite uno nuevo.
     *
     * @param  list<string>  $operators
     * @param  list<string>  $legacy
     * @return list<string>
     */
    public function authors(array $operators, array $legacy): array
    {
        $seen = [];
        foreach ([...$operators, ...$legacy] as $name) {
            if (! is_string($name)) {
                continue;
            }
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $seen[mb_strtolower($name)] ??= $name;
        }

        return array_values($seen);
    }

    /**
     * @param  list<array{id: string, name: string, active: bool, created_at: \DateTimeImmutable}>  $operators
     * @return list<array{id: string, name: string, active: bool, created_at: string}>
     */
    public function operators(array $operators): array
    {
        return array_map(fn (array $o) => [
            'id' => $o['id'],
            'name' => $o['name'],
            'active' => $o['active'],
            'created_at' => $o['created_at']->format(DATE_ATOM),
        ], $operators);
    }

    private function summary(array $i): array
    {
        return [
            'id' => $i['id'],
            'title' => $i['title'],
            'excerpt' => mb_strimwidth($i['description'], 0, self::EXCERPT_LENGTH, '…'),
            'status' => ['value' => $i['status'], 'label' => L::status($i['status'])],
            'priority' => ['value' => $i['priority'], 'label' => L::priority($i['priority']), 'weight' => Priority::from($i['priority'])->weight()],
            'requester_name' => $i['requester_name'],
            'assigned_to' => $i['assigned_to'],
            'comments_count' => $i['comments_count'],
            'created_at' => $i['created_at']->format(DATE_ATOM),
            'updated_at' => $i['updated_at']->format(DATE_ATOM),
            'due_at' => $i['due_at']->format(DATE_ATOM),
            'is_overdue' => $i['is_overdue'],
            'transitions' => array_map(fn (array $t) => [
                'value' => $t['status'],
                'label' => L::status($t['status']),
                'requires_reason' => $t['requires_reason'],
            ], $i['transitions']),
            'needs_review' => $i['review_flags'] !== [],
            'review_flags' => array_map(fn (array $f) => [
                'type' => $f['type'],
                'label' => DiagnosisLabels::typeFromValue($f['type']),
                'created_at' => $f['created_at']->format(DATE_ATOM),
            ], $i['review_flags']),
        ];
    }

    private function timelineEntry(array $e): array
    {
        $base = ['type' => $e['type'], 'id' => $e['id'], 'created_at' => $e['occurred_at']->format(DATE_ATOM)];

        if ($e['type'] === 'comment') {
            return $base + ['author_name' => $e['author_name'], 'body' => $e['body']];
        }

        $reconciled = $e['action'] === LogAction::RECONCILED->value;

        return $base + [
            'action' => $e['action'],
            'action_label' => L::logAction($e['action']),
            'old_value' => $e['old_value'],
            'old_label' => L::status($e['old_value']),
            'new_value' => $e['new_value'],
            'new_label' => L::status($e['new_value']),
            'user_name' => $e['actor'],
            'reason' => $e['metadata']['reason'] ?? null,
            'ip' => $e['metadata']['ip'] ?? null,
            'note' => $reconciled ? 'Asiento compensatorio generado por el diagnóstico de datos. No refleja un cambio real en esta fecha.' : null,
        ];
    }
}
