<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Sql;

use Exception;
use Illuminate\Database\ConnectionInterface;
use OptimaRetail\IncidentManagement\Application\Diagnostics\FixPolicy;
use OptimaRetail\IncidentManagement\Application\Diagnostics\Inconsistency;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyDetector;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyType;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Incident\LogAction;

/**
 * Detección en SQL portable (MySQL 8 / SQLite ≥ 3.28: subconsultas correlacionadas + LAG()).
 * Consultas documentadas en README §5.
 */
final class SqlInconsistencyDetector implements InconsistencyDetector
{
    /** Ventana para considerar dos asientos idénticos un duplicado (doble submit, retry...). */
    public const int DUPLICATE_WINDOW_SECONDS = 5;

    /** Último log de una incidencia: orden por fecha (µs) y desempate por UUIDv7. */
    private const string LAST_LOG = '(SELECT l2.id FROM incident_logs l2 WHERE l2.incident_id = i.id ORDER BY l2.created_at DESC, l2.id DESC LIMIT 1)';

    public function __construct(private readonly ConnectionInterface $db) {}

    public function detect(): array
    {
        $resolvedWithoutLog = $this->resolvedWithoutLog();
        $alreadyReported = array_flip(array_map(fn (Inconsistency $i) => $i->incidentId, $resolvedWithoutLog));

        return [
            ...$resolvedWithoutLog,
            ...array_values(array_filter($this->statusMismatch(), fn (Inconsistency $i) => ! isset($alreadyReported[$i->incidentId]))),
            ...$this->logSequenceIssues(),
        ];
    }

    /** (1) Resuelta sin asiento que la lleve a «resolved». */
    private function resolvedWithoutLog(): array
    {
        $actions = [...LogAction::transitionValues(), LogAction::RECONCILED->value];
        $placeholders = implode(', ', array_fill(0, count($actions), '?'));

        $rows = $this->db->select(
            'SELECT i.id, i.title, last.new_value AS last_logged
               FROM incidents i
               LEFT JOIN incident_logs last ON last.id = '.self::LAST_LOG.'
              WHERE i.status = ?
                AND NOT EXISTS (
                    SELECT 1 FROM incident_logs l
                     WHERE l.incident_id = i.id AND l.new_value = ? AND l.action IN ('.$placeholders.')
                )',
            [IncidentStatus::RESOLVED->value, IncidentStatus::RESOLVED->value, ...$actions]
        );

        return array_map(fn ($r) => new Inconsistency(
            type: InconsistencyType::RESOLVED_WITHOUT_LOG,
            incidentId: $r->id,
            policy: FixPolicy::AUTO,
            details: ['title' => $r->title, 'current_status' => IncidentStatus::RESOLVED->value, 'last_logged' => $r->last_logged],
        ), $rows);
    }

    /** (2) El estado no coincide con el último log (o no hay logs). */
    private function statusMismatch(): array
    {
        $rows = $this->db->select(
            'SELECT i.id, i.title, i.status, last.new_value AS last_logged, last.id AS last_log_id
               FROM incidents i
               LEFT JOIN incident_logs last ON last.id = '.self::LAST_LOG.'
              WHERE last.id IS NULL OR last.new_value <> i.status'
        );

        return array_map(fn ($r) => new Inconsistency(
            type: InconsistencyType::STATUS_LOG_MISMATCH,
            incidentId: $r->id,
            // La otra hipótesis (estado sobrescrito por error) solo la puede decidir una persona.
            policy: FixPolicy::NEEDS_CONFIRMATION,
            details: ['title' => $r->title, 'current_status' => $r->status, 'last_logged' => $r->last_logged, 'last_log_id' => $r->last_log_id],
        ), $rows);
    }

    /**
     * (3) Duplicados y (4) cadena incoherente, en una sola pasada con LAG().
     *
     * @throws Exception si created_at no es una fecha válida
     */
    private function logSequenceIssues(): array
    {
        $rows = $this->db->select(
            'SELECT l.id, l.incident_id, i.title, l.action, l.old_value, l.new_value, l.user_name, l.created_at,
                    LAG(l.id)         OVER w AS prev_id,
                    LAG(l.action)     OVER w AS prev_action,
                    LAG(l.old_value)  OVER w AS prev_old,
                    LAG(l.new_value)  OVER w AS prev_new,
                    LAG(l.user_name)  OVER w AS prev_user,
                    LAG(l.created_at) OVER w AS prev_created_at
               FROM incident_logs l
               JOIN incidents i ON i.id = l.incident_id
             WINDOW w AS (PARTITION BY l.incident_id ORDER BY l.created_at, l.id)
              ORDER BY l.incident_id, l.created_at, l.id'
        );

        $found = [];
        foreach ($rows as $r) {
            if ($r->prev_id === null) {
                continue; // primer asiento de la incidencia
            }

            $sameContent = $r->action === $r->prev_action
                && $r->old_value === $r->prev_old
                && $r->new_value === $r->prev_new
                && $r->user_name === $r->prev_user;
            $seconds = abs((new \DateTimeImmutable($r->created_at))->getTimestamp() - (new \DateTimeImmutable($r->prev_created_at))->getTimestamp());

            if ($sameContent && $seconds <= self::DUPLICATE_WINDOW_SECONDS) {
                $found[] = new Inconsistency(
                    type: InconsistencyType::DUPLICATE_LOG,
                    incidentId: $r->incident_id,
                    policy: FixPolicy::AUTO,
                    details: [
                        'title' => $r->title, 'duplicate_id' => $r->id, 'kept_id' => $r->prev_id, 'seconds_apart' => $seconds,
                        'action' => $r->action, 'old_value' => $r->old_value, 'new_value' => $r->new_value,
                    ],
                );

                continue;
            }

            $brokenLink = $r->old_value !== $r->prev_new;
            $forbidden = false;
            $wrongAction = false;
            if (LogAction::tryFrom($r->action)?->isTransition()) {
                // Reglas del dominio reutilizadas, no duplicadas.
                $from = IncidentStatus::tryFrom((string) $r->old_value);
                $to = IncidentStatus::tryFrom((string) $r->new_value);
                $forbidden = ! $from || ! $to || ! $from->canTransitionTo($to);
                // p. ej. resolved→open registrado como status_changed: reapertura sin log explícito.
                $wrongAction = ! $forbidden && LogAction::forTransition($from, $to)->value !== $r->action;
            }

            if ($brokenLink || $forbidden || $wrongAction) {
                $found[] = new Inconsistency(
                    type: InconsistencyType::BROKEN_LOG_CHAIN,
                    incidentId: $r->incident_id,
                    policy: FixPolicy::MANUAL,
                    details: [
                        'title' => $r->title, 'log_id' => $r->id, 'prev_log_id' => $r->prev_id,
                        'old_value' => $r->old_value, 'new_value' => $r->new_value, 'prev_new_value' => $r->prev_new,
                        'action' => $r->action, 'expected_action' => $wrongAction ? LogAction::forTransition($from, $to)->value : null,
                        'broken_link' => $brokenLink, 'forbidden_transition' => $forbidden, 'wrong_action' => $wrongAction,
                    ],
                );
            }
        }

        return $found;
    }
}
