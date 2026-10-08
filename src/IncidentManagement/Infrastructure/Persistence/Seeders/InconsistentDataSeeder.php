<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Simula a propósito las inconsistencias del Ejercicio 5 escribiendo en BD SALTÁNDOSE
 * el dominio (como haría un UPDATE manual, un script antiguo o un doble submit).
 * Consultas de detección documentadas en README §5.
 * php artisan db:seed --class='OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders\InconsistentDataSeeder'
 */
final class InconsistentDataSeeder extends Seeder
{
    public function run(): void
    {
        $t = now()->subDays(2);
        $ts = fn ($c, $mins) => $c->copy()->addMinutes($mins)->format('Y-m-d H:i:s.u');

        // (1) Resuelta sin log de resolución: UPDATE directo de status, sin asiento.
        $a = $this->incident('[SIM-1] Báscula de frutería descalibrada', 'resolved', $t);
        $this->log($a, 'created', null, 'open', $ts($t, 0));
        $this->log($a, 'status_changed', 'open', 'under_review', $ts($t, 10));

        // (2) Estado ≠ último log: el log dice «blocked» pero la tabla dice «under_review».
        $b = $this->incident('[SIM-2] Error al cerrar caja (arqueo)', 'under_review', $t);
        $this->log($b, 'created', null, 'open', $ts($t, 0));
        $this->log($b, 'status_changed', 'open', 'under_review', $ts($t, 15));
        $this->log($b, 'status_changed', 'under_review', 'blocked', $ts($t, 30), ['reason' => 'Pendiente de auditoría']);

        // (3a) Log duplicado: doble submit del mismo cambio con 1 s de diferencia.
        $c = $this->incident('[SIM-3] Etiquetadora no conecta por WiFi', 'under_review', $t);
        $this->log($c, 'created', null, 'open', $ts($t, 0));
        $this->log($c, 'status_changed', 'open', 'under_review', $t->copy()->addMinutes(5)->format('Y-m-d H:i:s.u'));
        $this->log($c, 'status_changed', 'open', 'under_review', $t->copy()->addMinutes(5)->addSecond()->format('Y-m-d H:i:s.u'));

        // (3b) Log incoherente: salto open→resolved (transición prohibida) y cadena rota.
        $d = $this->incident('[SIM-4] Promoción 3x2 no se aplica en TPV', 'resolved', $t);
        $this->log($d, 'created', null, 'open', $ts($t, 0));
        $this->log($d, 'status_changed', 'open', 'resolved', $ts($t, 20));

        // (3c) Reapertura sin log explícito: resolved→open registrado como «status_changed» en vez de «reopened».
        $e = $this->incident('[SIM-5] Cajón portamonedas vuelve a fallar', 'open', $t);
        $this->log($e, 'created', null, 'open', $ts($t, 0));
        $this->log($e, 'status_changed', 'open', 'under_review', $ts($t, 10));
        $this->log($e, 'status_changed', 'under_review', 'resolved', $ts($t, 20));
        $this->log($e, 'status_changed', 'resolved', 'open', $ts($t, 40));
    }

    private function incident(string $title, string $status, $at): string
    {
        $id = (string) Str::uuid7();
        DB::table('incidents')->insert([
            'id' => $id, 'title' => $title, 'description' => 'Dato simulado para el diagnóstico de inconsistencias.',
            'status' => $status, 'priority' => 'medium', 'requester_name' => 'Simulación',
            'assigned_to' => null, 'created_at' => $at, 'updated_at' => $at,
        ]);

        return $id;
    }

    private function log(string $incidentId, string $action, ?string $old, string $new, string $at, ?array $meta = null): void
    {
        DB::table('incident_logs')->insert([
            'id' => (string) Str::uuid7(), 'incident_id' => $incidentId, 'action' => $action,
            'old_value' => $old, 'new_value' => $new, 'user_name' => 'legacy-script',
            'metadata' => $meta ? json_encode($meta) : null, 'created_at' => $at,
        ]);
    }
}
