<?php

namespace Tests\Unit\IncidentManagement\Domain\Operator;

use OptimaRetail\IncidentManagement\Domain\Operator\Exception\InvalidOperatorData;
use OptimaRetail\IncidentManagement\Domain\Operator\Operator;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorId;
use PHPUnit\Framework\TestCase;

final class OperatorTest extends TestCase
{
    public function test_register_trims_name_and_starts_active(): void
    {
        $operator = Operator::register(
            OperatorId::fromString('02b22b72-0000-7000-8000-000000000001'),
            '  Marta Soler ',
            new \DateTimeImmutable('2026-10-08 10:00:00'),
        );

        $this->assertSame('Marta Soler', $operator->name());
        $this->assertTrue($operator->isActive());
    }

    public function test_register_rejects_blank_and_too_short_names(): void
    {
        $this->expectException(InvalidOperatorData::class);
        Operator::register(
            OperatorId::fromString('02b22b72-0000-7000-8000-000000000001'),
            ' ',
            new \DateTimeImmutable,
        );
    }

    public function test_register_rejects_single_character_name(): void
    {
        try {
            Operator::register(
                OperatorId::fromString('02b22b72-0000-7000-8000-000000000001'),
                'x',
                new \DateTimeImmutable,
            );
            $this->fail('debería lanzar InvalidOperatorData');
        } catch (InvalidOperatorData $e) {
            $this->assertSame(InvalidOperatorData::TOO_SHORT, $e->rule);
        }
    }

    public function test_deactivate_and_reactivate(): void
    {
        $operator = Operator::register(
            OperatorId::fromString('02b22b72-0000-7000-8000-000000000001'),
            'Jordi Puig',
            new \DateTimeImmutable,
        );

        $operator->deactivate();
        $this->assertFalse($operator->isActive());

        $operator->reactivate();
        $this->assertTrue($operator->isActive());
    }
}
