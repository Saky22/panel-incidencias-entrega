<?php

namespace Tests\Unit\IncidentManagement\Domain\Incident;

use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus as S;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IncidentStatusTest extends TestCase
{
    public static function transitions(): array
    {
        $allowed = [
            'open>under_review', 'under_review>blocked', 'under_review>resolved',
            'blocked>under_review', 'resolved>open',
        ];
        $cases = [];
        foreach (S::cases() as $from) {
            foreach (S::cases() as $to) {
                $key = "{$from->value}>{$to->value}";
                $cases[$key] = [$from, $to, in_array($key, $allowed, true)];
            }
        }

        return $cases;
    }

    #[DataProvider('transitions')]
    public function test_transition_matrix(S $from, S $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    public function test_sensitive_transitions_require_reason(): void
    {
        $this->assertTrue(S::UNDER_REVIEW->requiresReasonFor(S::BLOCKED));
        $this->assertTrue(S::BLOCKED->requiresReasonFor(S::UNDER_REVIEW));
        $this->assertTrue(S::RESOLVED->requiresReasonFor(S::OPEN));
        $this->assertFalse(S::OPEN->requiresReasonFor(S::UNDER_REVIEW));
        $this->assertFalse(S::UNDER_REVIEW->requiresReasonFor(S::RESOLVED));
    }

    public function test_values_are_centralised(): void
    {
        $this->assertSame(['open', 'under_review', 'blocked', 'resolved'], S::values());
    }
}
