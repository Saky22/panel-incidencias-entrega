<?php

namespace Tests\Feature\IncidentManagement\UI\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders\IncidentSeeder;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders\InconsistentDataSeeder;
use Tests\TestCase;

final class DiagnoseIncidentsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function findings(): array
    {
        Artisan::call('incidents:diagnose', ['--json' => true]);

        return json_decode(Artisan::output(), true)['findings'];
    }

    private function types(array $findings): array
    {
        $types = array_column($findings, 'type');
        sort($types);

        return $types;
    }

    public function test_clean_data_has_no_findings(): void
    {
        $this->seed(IncidentSeeder::class);
        $this->assertSame([], $this->findings());
    }

    public function test_detects_the_simulated_inconsistencies(): void
    {
        $this->seed([IncidentSeeder::class, InconsistentDataSeeder::class]);

        $this->assertSame(
            // SIM-4 (transición prohibida) y SIM-5 (reapertura sin log explícito) son cadenas incoherentes.
            ['broken_log_chain', 'broken_log_chain', 'duplicate_log', 'resolved_without_log', 'status_log_mismatch'],
            $this->types($this->findings())
        );
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->seed(InconsistentDataSeeder::class);
        $before = DB::table('incident_logs')->count();

        $this->artisan('incidents:diagnose')->assertSuccessful();

        $this->assertSame($before, DB::table('incident_logs')->count());
        $this->assertSame(0, DB::table('incident_review_flags')->count());
    }

    public function test_fix_applies_only_safe_corrections_and_is_idempotent(): void
    {
        Storage::fake('local');
        $this->seed(InconsistentDataSeeder::class);
        $statusesBefore = DB::table('incidents')->pluck('status', 'id')->all();

        $this->artisan('incidents:diagnose', ['--fix' => true, '--no-interaction' => true])->assertSuccessful();

        // Nunca se toca incidents.status.
        $this->assertSame($statusesBefore, DB::table('incidents')->pluck('status', 'id')->all());
        // Asiento compensatorio, no reescritura.
        $this->assertSame(1, DB::table('incident_logs')->where('action', 'reconciled')->count());
        // Duplicado borrado con copia previa.
        Storage::disk('local')->assertExists('diagnostics/removed_logs_'.now()->format('Ymd').'.jsonl');

        // Lo que no se corrige queda marcado para revisión manual (sin tocar datos).
        $this->assertSame(['broken_log_chain', 'broken_log_chain', 'status_log_mismatch'], $this->types($this->findings()));
        $this->assertSame(3, DB::table('incident_review_flags')->whereNull('resolved_at')->count());

        // Idempotente: una segunda pasada no duplica marcas.
        $this->artisan('incidents:diagnose', ['--fix' => true, '--no-interaction' => true])->assertSuccessful();
        $this->assertSame(3, DB::table('incident_review_flags')->count());

        // Con confirmación explícita se reconcilia el desajuste y su marca se cierra sola.
        $this->artisan('incidents:diagnose', ['--fix' => true, '--accept-current-status' => true, '--no-interaction' => true]);
        $this->assertSame(['broken_log_chain', 'broken_log_chain'], $this->types($this->findings()));
        $this->assertSame(2, DB::table('incident_review_flags')->whereNull('resolved_at')->count());
    }
}
