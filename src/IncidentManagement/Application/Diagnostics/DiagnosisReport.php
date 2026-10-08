<?php

namespace OptimaRetail\IncidentManagement\Application\Diagnostics;

final class DiagnosisReport
{
    /** @var list<array{inconsistency: Inconsistency, outcome: DiagnosisOutcome, result: array|string|null}> */
    private array $entries = [];

    /** Marcas de revisión cerradas porque su inconsistencia ya no se detecta. */
    public int $closedFlags = 0;

    public function __construct(public readonly bool $dryRun) {}

    public function add(Inconsistency $inconsistency, DiagnosisOutcome $outcome, array|string|null $result = null): void
    {
        $this->entries[] = ['inconsistency' => $inconsistency, 'outcome' => $outcome, 'result' => $result];
    }

    /** @return list<array{inconsistency: Inconsistency, outcome: DiagnosisOutcome, result: array|string|null}> */
    public function entries(): array
    {
        return $this->entries;
    }

    public function count(?DiagnosisOutcome $outcome = null): int
    {
        return count(array_filter($this->entries, fn ($e) => $outcome === null || $e['outcome'] === $outcome));
    }

    public function hasFailures(): bool
    {
        return $this->count(DiagnosisOutcome::FAILED) > 0;
    }
}
