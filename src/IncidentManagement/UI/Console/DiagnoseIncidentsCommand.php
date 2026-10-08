<?php

namespace OptimaRetail\IncidentManagement\UI\Console;

use Illuminate\Console\Command;
use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnoseIncidentData\DiagnoseIncidentData;
use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnoseIncidentData\DiagnoseIncidentDataHandler;
use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnosisOutcome as O;
use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnosisReport;
use OptimaRetail\IncidentManagement\UI\Labels\DiagnosisLabels as L;

/** Adaptador primario CLI del diagnóstico de datos. */
final class DiagnoseIncidentsCommand extends Command
{
    protected $signature = 'incidents:diagnose
        {--fix : Aplica las correcciones seguras (por defecto solo dry-run)}
        {--accept-current-status : Con --fix, reconcilia también «estado ≠ último log» asumiendo el estado actual como verdad}
        {--actor=system:diagnostics : Nombre que figurará en los asientos de reconciliación}
        {--json : Salida en JSON (para CI / monitorización)}';

    protected $description = 'Detecta inconsistencias entre incidencias y su trazabilidad; corrige solo con --fix';

    public function handle(DiagnoseIncidentDataHandler $handler): int
    {
        $fix = (bool) $this->option('fix');

        if ($fix && ! $this->option('json') && $this->input->isInteractive()
            && ! $this->confirm('Se van a escribir cambios en la base de datos. ¿Continuar?', true)) {
            return self::FAILURE;
        }

        $report = $handler->handle(new DiagnoseIncidentData(
            fix: $fix,
            acceptAssumptions: (bool) $this->option('accept-current-status'),
            actor: (string) $this->option('actor'),
        ));

        $this->option('json') ? $this->renderJson($report) : $this->renderTable($report);

        return $report->hasFailures() ? self::FAILURE : self::SUCCESS;
    }

    private function renderJson(DiagnosisReport $report): void
    {
        $this->line(json_encode([
            'dry_run' => $report->dryRun,
            'closed_review_flags' => $report->closedFlags,
            'findings' => array_map(fn ($e) => $e['inconsistency']->toArray() + [
                'outcome' => $e['outcome']->value,
                'result' => $e['result'],
            ], $report->entries()),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function renderTable(DiagnosisReport $report): void
    {
        $this->components->info($report->dryRun ? 'Modo DRY-RUN: no se modifica nada (usa --fix para corregir).' : 'Modo CORRECCIÓN.');

        if ($report->count() === 0) {
            $this->components->info('Sin inconsistencias. ✔');
            if ($report->closedFlags > 0) {
                $this->line("Marcas de revisión cerradas: {$report->closedFlags}");
            }

            return;
        }

        $this->table(
            ['Tipo', 'Incidencia', 'Problema', 'Corrección', 'Resultado'],
            array_map(fn ($e) => [
                L::type($e['inconsistency']->type),
                wordwrap($e['inconsistency']->details['title'] ?? '', 36)."\n".$e['inconsistency']->incidentId,
                wordwrap(L::problem($e['inconsistency']), 50),
                wordwrap(L::proposedFix($e['inconsistency']), 50),
                L::outcome($e['outcome']).(($r = L::result($e['result'])) ? "\n".wordwrap($r, 40) : ''),
            ], $report->entries()),
        );

        $this->newLine();
        $this->line(sprintf(
            'Total: %d · se corregirían: %d · requieren confirmación: %d · revisión manual: %d · corregidas: %d · marcadas para revisión: %d · fallidas: %d',
            $report->count(), $report->count(O::WOULD_FIX), $report->count(O::NEEDS_CONFIRMATION), $report->count(O::MANUAL_REVIEW),
            $report->count(O::FIXED), $report->count(O::FLAGGED_FOR_REVIEW), $report->count(O::FAILED),
        ));
        if ($report->closedFlags > 0) {
            $this->line("Marcas de revisión cerradas (la inconsistencia ya no existe): {$report->closedFlags}");
        }
    }
}
