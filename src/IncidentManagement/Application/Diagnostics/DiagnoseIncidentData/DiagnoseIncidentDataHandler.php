<?php

namespace OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnoseIncidentData;

use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnosisOutcome;
use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnosisReport;
use OptimaRetail\IncidentManagement\Application\Diagnostics\FixPolicy;
use OptimaRetail\IncidentManagement\Application\Diagnostics\Inconsistency;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyDetector;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyRepairer;
use OptimaRetail\Shared\Application\Transaction\TransactionManager;
use OptimaRetail\Shared\Domain\Clock;

/**
 * Detecta inconsistencias y, solo con --fix, actúa con prudencia:
 *
 *  - AUTO                   → se corrige (asiento «reconciled» o borrado de duplicado con copia).
 *  - NEEDS_CONFIRMATION     → se corrige solo si se aceptan las hipótesis; si no, se marca para revisión.
 *  - MANUAL                 → nunca se corrige: se marca para revisión manual.
 *
 * Nunca cambia incidents.status ni reescribe un log. Una transacción por acción.
 * Las marcas cuya inconsistencia ya no existe se cierran solas.
 */
final readonly class DiagnoseIncidentDataHandler
{
    public function __construct(
        private InconsistencyDetector $detector,
        private InconsistencyRepairer $repairer,
        private TransactionManager $tx,
        private Clock $clock,
    ) {}

    public function handle(DiagnoseIncidentData $command): DiagnosisReport
    {
        $report = new DiagnosisReport(dryRun: ! $command->fix);

        foreach ($this->detector->detect() as $inconsistency) {
            $canFix = $inconsistency->policy->allows($command->acceptAssumptions);

            if (! $command->fix) {
                $report->add($inconsistency, match (true) {
                    $inconsistency->policy === FixPolicy::MANUAL => DiagnosisOutcome::MANUAL_REVIEW,
                    ! $canFix => DiagnosisOutcome::NEEDS_CONFIRMATION,
                    default => DiagnosisOutcome::WOULD_FIX,
                });

                continue;
            }

            $canFix
                ? $this->fix($inconsistency, $command->actor, $report)
                : $this->flag($inconsistency, $command->actor, $report);
        }

        if ($command->fix) {
            $stillPending = array_map(
                fn (array $e) => $e['inconsistency'],
                array_filter($report->entries(), fn (array $e) => $e['outcome'] !== DiagnosisOutcome::FIXED),
            );
            $report->closedFlags = $this->tx->run(fn () => $this->repairer->closeResolvedFlags(array_values($stillPending), $this->clock->now()));
        }

        return $report;
    }

    private function fix(Inconsistency $inconsistency, string $actor, DiagnosisReport $report): void
    {
        try {
            $result = $this->tx->run(fn () => $this->repairer->repair($inconsistency, $actor, $this->clock->now()));
            $report->add($inconsistency, DiagnosisOutcome::FIXED, $result);
        } catch (\Throwable $e) {
            $report->add($inconsistency, DiagnosisOutcome::FAILED, $e->getMessage());
        }
    }

    private function flag(Inconsistency $inconsistency, string $actor, DiagnosisReport $report): void
    {
        try {
            $created = $this->tx->run(fn () => $this->repairer->flagForReview($inconsistency, $actor, $this->clock->now()));
            $report->add($inconsistency, DiagnosisOutcome::FLAGGED_FOR_REVIEW, ['action' => $created ? 'flag_created' : 'flag_exists']);
        } catch (\Throwable $e) {
            $report->add($inconsistency, DiagnosisOutcome::FAILED, $e->getMessage());
        }
    }
}
