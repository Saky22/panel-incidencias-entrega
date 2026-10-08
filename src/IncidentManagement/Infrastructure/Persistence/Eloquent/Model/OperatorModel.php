<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Modelo de persistencia del catálogo de operadores (adaptador, sin reglas). */
final class OperatorModel extends Model
{
    use HasUuids;

    protected $table = 'operators';

    protected $guarded = [];

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'immutable_datetime',
    ];
}
