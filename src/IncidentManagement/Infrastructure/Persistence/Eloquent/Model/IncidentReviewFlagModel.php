<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class IncidentReviewFlagModel extends Model
{
    use HasUuids;

    public const null UPDATED_AT = null;

    protected $table = 'incident_review_flags';

    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $casts = [
        'details' => 'array',
        'created_at' => 'immutable_datetime',
        'resolved_at' => 'immutable_datetime',
    ];

    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }
}
