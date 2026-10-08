<?php

namespace Tests\Unit\IncidentManagement\Application\Command;

use OptimaRetail\IncidentManagement\Application\Command\DeactivateOperator\DeactivateOperator;
use OptimaRetail\IncidentManagement\Application\Command\DeactivateOperator\DeactivateOperatorHandler;
use OptimaRetail\IncidentManagement\Application\Command\RegisterOperator\RegisterOperator;
use OptimaRetail\IncidentManagement\Application\Command\RegisterOperator\RegisterOperatorHandler;
use OptimaRetail\IncidentManagement\Domain\Operator\Exception\OperatorAlreadyRegistered;
use OptimaRetail\IncidentManagement\Domain\Operator\Exception\OperatorNotFound;
use PHPUnit\Framework\TestCase;
use Tests\Support\FrozenClock;
use Tests\Support\ImmediateTransactionManager;
use Tests\Support\InMemoryOperatorRepository;

/** Casos de uso de operadores aislados: puertos sustituidos por adaptadores en memoria. */
final class OperatorCommandHandlersTest extends TestCase
{
    private InMemoryOperatorRepository $repo;

    private ImmediateTransactionManager $tx;

    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->repo = new InMemoryOperatorRepository;
        $this->tx = new ImmediateTransactionManager;
        $this->clock = new FrozenClock;
    }

    public function test_register_persists_active_operator_with_clock_time(): void
    {
        $id = (new RegisterOperatorHandler($this->repo, $this->tx, $this->clock))
            ->handle(new RegisterOperator('Marta Soler'));

        $operator = $this->repo->find($id);
        $this->assertSame('Marta Soler', $operator->name());
        $this->assertTrue($operator->isActive());
        $this->assertEquals($this->clock->now(), $operator->createdAt());
        $this->assertSame(1, $this->tx->runs);
    }

    public function test_register_active_name_throws(): void
    {
        (new RegisterOperatorHandler($this->repo, $this->tx, $this->clock))
            ->handle(new RegisterOperator('Marta Soler'));

        $this->expectException(OperatorAlreadyRegistered::class);
        (new RegisterOperatorHandler($this->repo, $this->tx, $this->clock))
            ->handle(new RegisterOperator('  marta soler '));
    }

    public function test_register_reactivates_deactivated_name(): void
    {
        $register = new RegisterOperatorHandler($this->repo, $this->tx, $this->clock);
        $id = $register->handle(new RegisterOperator('Jordi Puig'));
        (new DeactivateOperatorHandler($this->repo, $this->tx))->handle(new DeactivateOperator($id->value));

        $this->assertFalse($this->repo->find($id)->isActive());

        $sameId = $register->handle(new RegisterOperator('Jordi Puig'));

        $this->assertSame($id->value, $sameId->value);
        $this->assertTrue($this->repo->find($id)->isActive());
    }

    public function test_deactivate_unknown_operator_throws(): void
    {
        $this->expectException(OperatorNotFound::class);
        (new DeactivateOperatorHandler($this->repo, $this->tx))
            ->handle(new DeactivateOperator('02b22b72-0000-7000-8000-000000000000'));
    }
}
