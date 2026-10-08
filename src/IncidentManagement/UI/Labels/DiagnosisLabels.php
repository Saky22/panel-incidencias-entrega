<?php

namespace OptimaRetail\IncidentManagement\UI\Labels;

use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnosisOutcome;
use OptimaRetail\IncidentManagement\Application\Diagnostics\Inconsistency;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyType;
use OptimaRetail\IncidentManagement\UI\Labels\IncidentLabels as L;

/** Textos (es-ES) del diagnóstico, construidos a partir de los datos estructurados del hallazgo. */
final class DiagnosisLabels
{
    public static function type(InconsistencyType $type): string
    {
        return match ($type) {
            InconsistencyType::RESOLVED_WITHOUT_LOG => 'Resuelta sin log de resolución',
            InconsistencyType::STATUS_LOG_MISMATCH => 'Estado ≠ último log',
            InconsistencyType::DUPLICATE_LOG => 'Log duplicado',
            InconsistencyType::BROKEN_LOG_CHAIN => 'Log incoherente (cadena rota)',
        };
    }

    public static function typeFromValue(string $type): string
    {
        $case = InconsistencyType::tryFrom($type);

        return $case ? self::type($case) : $type;
    }

    public static function outcome(DiagnosisOutcome $outcome): string
    {
        return match ($outcome) {
            DiagnosisOutcome::WOULD_FIX => 'se corregiría',
            DiagnosisOutcome::NEEDS_CONFIRMATION => 'requiere confirmación (se marcaría)',
            DiagnosisOutcome::MANUAL_REVIEW => 'revisión manual (se marcaría)',
            DiagnosisOutcome::FIXED => 'corregida',
            DiagnosisOutcome::FLAGGED_FOR_REVIEW => 'marcada para revisión',
            DiagnosisOutcome::FAILED => 'fallida',
        };
    }

    public static function problem(Inconsistency $i): string
    {
        $d = $i->details;
        $last = $d['last_logged'] ?? null;

        return match ($i->type) {
            InconsistencyType::RESOLVED_WITHOUT_LOG => 'Está «Resuelta» pero ningún log registra la resolución (último log: '.(L::status($last) ?? 'ninguno').').',
            InconsistencyType::STATUS_LOG_MISMATCH => 'Estado actual «'.L::status($d['current_status']).'» pero el último log dice «'.(L::status($last) ?? 'sin logs').'».',
            InconsistencyType::DUPLICATE_LOG => "Log {$d['duplicate_id']} ({$d['old_value']}→{$d['new_value']}) repite al {$d['kept_id']} con {$d['seconds_apart']} s de diferencia.",
            InconsistencyType::BROKEN_LOG_CHAIN => "Log {$d['log_id']}: ".implode('; ', array_filter([
                $d['broken_link'] ? 'old_value «'.($d['old_value'] ?? '∅')."» no enlaza con el log anterior («{$d['prev_new_value']}»)" : null,
                $d['forbidden_transition'] ? "transición no permitida {$d['old_value']}→{$d['new_value']}" : null,
                ($d['wrong_action'] ?? false) ? "registrado como «{$d['action']}» en vez de «{$d['expected_action']}» (p. ej. reapertura sin log explícito)" : null,
            ])).'.',
        };
    }

    public static function proposedFix(Inconsistency $i): string
    {
        $d = $i->details;

        return match ($i->type) {
            InconsistencyType::RESOLVED_WITHOUT_LOG => 'Añadir asiento «reconciled» '.($d['last_logged'] ?? '∅').' → resolved. No se toca el estado.',
            InconsistencyType::STATUS_LOG_MISMATCH => 'Con --accept-current-status: asumir el estado actual como verdad y añadir asiento «reconciled» '.($d['last_logged'] ?? '∅')." → {$d['current_status']}. Sin él: se marca para revisión manual.",
            InconsistencyType::DUPLICATE_LOG => "Eliminar {$d['duplicate_id']} conservando {$d['kept_id']} (copia previa en storage/app/diagnostics).",
            InconsistencyType::BROKEN_LOG_CHAIN => 'No se corrige (no hay información para reconstruir lo que pasó): se marca para revisión manual.',
        };
    }

    public static function result(array|string|null $result): ?string
    {
        if (! is_array($result)) {
            return $result;
        }

        return match ($result['action'] ?? null) {
            'reconciled_log_added' => "Añadido log reconciled {$result['log_id']} (".($result['from'] ?? '∅')." → {$result['to']}).",
            'duplicate_removed' => "Eliminado {$result['log_id']} (copia en storage/app/{$result['backup']}).",
            'flag_created' => 'Marca de revisión creada (visible en el panel).',
            'flag_exists' => 'Ya estaba marcada.',
            default => json_encode($result, JSON_UNESCAPED_UNICODE),
        };
    }
}
