<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Sql;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use OptimaRetail\IncidentManagement\Application\Diagnostics\Inconsistency;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyRepairer;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyType;
use OptimaRetail\IncidentManagement\Domain\Incident\LogAction;

/**
 * Cada reparación revalida la condición con bloqueo dentro de la transacción
 * y aborta si los datos cambiaron desde la detección.
 */
final class SqlInconsistencyRepairer implements InconsistencyRepairer
{
    public const string BACKUP_DIR = 'diagnostics';

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Filesystem $disk,
    ) {}

    public function repair(Inconsistency $inconsistency, string $actor, \DateTimeImmutable $at): array
    {
        return match ($inconsistency->type) {
            InconsistencyType::RESOLVED_WITHOUT_LOG,
            InconsistencyType::STATUS_LOG_MISMATCH => $this->appendReconciliation($inconsistency, $actor, $at),
            InconsistencyType::DUPLICATE_LOG => $this->removeDuplicate($inconsistency, $at),
            InconsistencyType::BROKEN_LOG_CHAIN => throw new \LogicException('Las cadenas rotas no se corrigen automáticamente.'),
        };
    }

    public function flagForReview(Inconsistency $inconsistency, string $actor, \DateTimeImmutable $at): bool
    {
        $exists = $this->db->table('incident_review_flags')
            ->where('incident_id', $inconsistency->incidentId)
            ->where('inconsistency_type', $inconsistency->type->value)
            ->whereNull('resolved_at')
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            return false;
        }

        $this->db->table('incident_review_flags')->insert([
            'id' => (string) Str::uuid7(),
            'incident_id' => $inconsistency->incidentId,
            'inconsistency_type' => $inconsistency->type->value,
            'details' => json_encode($inconsistency->details, JSON_UNESCAPED_UNICODE),
            'flagged_by' => $actor,
            'created_at' => $at->format('Y-m-d H:i:s.u'),
        ]);

        return true;
    }

    public function closeResolvedFlags(array $stillPending, \DateTimeImmutable $at): int
    {
        $keep = array_map(fn (Inconsistency $i) => $i->incidentId.'|'.$i->type->value, $stillPending);

        $toClose = $this->db->table('incident_review_flags')
            ->whereNull('resolved_at')
            ->get(['id', 'incident_id', 'inconsistency_type'])
            ->reject(fn ($f) => in_array($f->incident_id.'|'.$f->inconsistency_type, $keep, true))
            ->pluck('id');

        return $toClose->isEmpty() ? 0 : $this->db->table('incident_review_flags')
            ->whereIn('id', $toClose->all())
            ->update(['resolved_at' => $at->format('Y-m-d H:i:s.u')]);
    }

    private function appendReconciliation(Inconsistency $inc, string $actor, \DateTimeImmutable $at): array
    {
        $incident = $this->db->table('incidents')->where('id', $inc->incidentId)->lockForUpdate()->first();
        $last = $this->db->table('incident_logs')
            ->where('incident_id', $inc->incidentId)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->first();

        if (! $incident
            || $incident->status !== $inc->details['current_status']
            || ($last->new_value ?? null) !== $inc->details['last_logged']) {
            throw new \RuntimeException('Los datos cambiaron desde la detección; vuelve a ejecutar el diagnóstico.');
        }

        $id = (string) Str::uuid7();
        $this->db->table('incident_logs')->insert([
            'id' => $id,
            'incident_id' => $inc->incidentId,
            'action' => LogAction::RECONCILED->value,
            'old_value' => $inc->details['last_logged'],
            'new_value' => $incident->status,
            'user_name' => $actor,
            'metadata' => json_encode(['inconsistency' => $inc->type->value], JSON_UNESCAPED_UNICODE),
            'created_at' => $at->format('Y-m-d H:i:s.u'),
        ]);

        return ['action' => 'reconciled_log_added', 'log_id' => $id, 'from' => $inc->details['last_logged'], 'to' => $incident->status];
    }

    private function removeDuplicate(Inconsistency $inc, \DateTimeImmutable $at): array
    {
        ['duplicate_id' => $dupId, 'kept_id' => $keptId] = $inc->details;

        $dup = $this->db->table('incident_logs')->where('id', $dupId)->lockForUpdate()->first();
        $kept = $this->db->table('incident_logs')->where('id', $keptId)->first();

        foreach (['incident_id', 'action', 'old_value', 'new_value', 'user_name'] as $field) {
            if (! $dup || ! $kept || $dup->{$field} !== $kept->{$field}) {
                throw new \RuntimeException("El log {$dupId} ya no es un duplicado exacto de {$keptId}; no se borra.");
            }
        }

        // Copia previa de un registro de auditoría (JSON Lines, una línea por log).
        $file = self::BACKUP_DIR.'/removed_logs_'.$at->format('Ymd').'.jsonl';
        $this->disk->append($file, json_encode(['removed_at' => $at->format(DATE_ATOM), 'kept_id' => $keptId, 'log' => $dup], JSON_UNESCAPED_UNICODE));

        $this->db->table('incident_logs')->where('id', $dupId)->delete();

        return ['action' => 'duplicate_removed', 'log_id' => $dupId, 'kept_id' => $keptId, 'backup' => $file];
    }
}
