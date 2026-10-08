<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use OptimaRetail\IncidentManagement\Domain\Incident\LogAction;

/**
 * Log de auditoría append-only: Eloquent no permite modificarlo ni borrarlo.
 * Único borrado posible: reparación explícita del diagnóstico (SqlInconsistencyRepairer, con copia previa).
 */
final class IncidentLogModel extends Model
{
    use HasUuids;

    public const null UPDATED_AT = null;

    protected $table = 'incident_logs';

    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $casts = [
        'action' => LogAction::class,
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        self::updating(fn () => throw new \LogicException('Los logs de incidencias son inmutables.'));
        self::deleting(fn () => throw new \LogicException('Los logs de incidencias son inmutables.'));
    }
}
