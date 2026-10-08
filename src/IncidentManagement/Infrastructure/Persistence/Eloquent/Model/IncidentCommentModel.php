<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class IncidentCommentModel extends Model
{
    use HasUuids;

    protected $table = 'incident_comments';

    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $casts = [
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
