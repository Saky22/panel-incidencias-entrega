<?php

namespace Tests\Unit\IncidentManagement\Application\Diagnostics;

use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnoseIncidentData\DiagnoseIncidentData;
use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnoseIncidentData\DiagnoseIncidentDataHandler;
use OptimaRetail\IncidentManagement\Application\Diagnostics\DiagnosisOutcome as O;
use OptimaRetail\IncidentManagement\Application\Diagnostics\FixPolicy;
use OptimaRetail\IncidentManagement\Application\Diagnostics\Inconsistency;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyDetector;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyRepairer;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyType as T;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\FrozenClock;
use Tests\Support\ImmediateTransactionManager;

/** Política de prudencia del diagnóstico, independiente de SQL. */
final class DiagnoseIncidentDataHandlerTest extends TestCase
{
    private object $repairer;

    protected function setUp(): void
    {
        $this->repairer = new class implements InconsistencyRepairer
        {
            /** @var list<string> */
            public array $repaired = [];

            /** @var list<string> */
            public array $flagged = [];

            /** @var list<string> */
            public array $pendingOnClose = [];

            public function repair(Inconsistency $i, string $actor, \DateTimeImmutable $at): array
            {
                if ($i->incidentId === 'boom') {
                    throw new \RuntimeException('cambió');
                }
                $this->repaired[] = $i->incidentId;

                return ['action' => 'ok'];
            }

            public function flagForReview(Inconsistency $i, string $actor, \DateTimeImmutable $at): bool
            {
                $this->flagged[] = $i->incidentId;

                return true;
            }

            public function closeResolvedFlags(array $stillPending, \DateTimeImmutable $at): int
            {
                $this->pendingOnClose = array_map(fn (Inconsistency $i) => $i->incidentId, $stillPending);

                return 0;
            }
        };
    }

    private function handler(): DiagnoseIncidentDataHandler
    {
        $detector = new class implements InconsistencyDetector
        {
            public function detect(): array
            {
                return [
                    new Inconsistency(T::RESOLVED_WITHOUT_LOG, 'a', FixPolicy::AUTO),
                    new Inconsistency(T::STATUS_LOG_MISMATCH, 'b', FixPolicy::NEEDS_CONFIRMATION),
                    new Inconsistency(T::BROKEN_LOG_CHAIN, 'c', FixPolicy::MANUAL),
                    new Inconsistency(T::DUPLICATE_LOG, 'boom', FixPolicy::AUTO),
                ];
            }
        };

        return new DiagnoseIncidentDataHandler($detector, $this->repairer, new ImmediateTransactionManager, new FrozenClock);
    }

    public static function scenarios(): array
    {
        return [
            'dry-run: no escribe nada' => [false, false,
                [O::WOULD_FIX, O::NEEDS_CONFIRMATION, O::MANUAL_REVIEW, O::WOULD_FIX], [], [], null],
            'fix: corrige lo seguro, marca el resto' => [true, false,
                [O::FIXED, O::FLAGGED_FOR_REVIEW, O::FLAGGED_FOR_REVIEW, O::FAILED], ['a'], ['b', 'c'], ['b', 'c', 'boom']],
            'fix + confirmación: también reconcilia' => [true, true,
                [O::FIXED, O::FIXED, O::FLAGGED_FOR_REVIEW, O::FAILED], ['a', 'b'], ['c'], ['c', 'boom']],
        ];
    }

    #[DataProvider('scenarios')]
    public function test_outcomes_by_policy(bool $fix, bool $confirm, array $outcomes, array $repaired, array $flagged, ?array $pending): void
    {
        $report = $this->handler()->handle(new DiagnoseIncidentData($fix, $confirm));

        $this->assertSame($outcomes, array_map(fn ($e) => $e['outcome'], $report->entries()));
        $this->assertSame($repaired, $this->repairer->repaired, 'MANUAL nunca se repara; un fallo no detiene el resto');
        $this->assertSame($flagged, $this->repairer->flagged);
        if ($pending !== null) {
            $this->assertSame($pending, $this->repairer->pendingOnClose, 'lo no corregido (incluidos fallos) mantiene su marca');
        }
        $this->assertSame(! $fix, $report->dryRun);
    }
}
