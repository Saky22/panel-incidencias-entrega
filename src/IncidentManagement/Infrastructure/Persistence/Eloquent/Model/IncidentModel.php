<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Incident\Priority;

/** Modelo de persistencia (adaptador). Sin reglas de negocio: viven en el dominio. */
final class IncidentModel extends Model
{
    use HasUuids;

    protected $table = 'incidents';

    protected $guarded = [];

    public $timestamps = false; // las fechas las gobierna el dominio (Clock)

    protected $casts = [
        'status' => IncidentStatus::class,
        'priority' => Priority::class,
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(IncidentLogModel::class, 'incident_id')->orderBy('created_at')->orderBy('id');
    }

    public function openReviewFlags(): HasMany
    {
        return $this->hasMany(IncidentReviewFlagModel::class, 'incident_id')->whereNull('resolved_at')->orderBy('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IncidentCommentModel::class, 'incident_id')->orderBy('created_at')->orderBy('id');
    }
}
