<?php

namespace Tests\Feature\IncidentManagement\UI\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use OptimaRetail\IncidentManagement\Domain\Operator\Exception\OperatorAlreadyRegistered;
use OptimaRetail\IncidentManagement\Domain\Operator\Operator;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorRepository;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\OperatorModel;
use Tests\TestCase;

final class OperatorsHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_operators(): void
    {
        $this->post('/operators', ['name' => 'Marta Soler'])->assertSessionHasNoErrors();

        $this->get('/operators')
            ->assertInertia(fn (Assert $p) => $p
                ->component('Operators/Index')
                ->has('operators', 1)
                ->where('operators.0.name', 'Marta Soler')
                ->where('operators.0.active', true));
    }

    public function test_store_validates_name(): void
    {
        $this->post('/operators', ['name' => 'x'])
            ->assertSessionHasErrors(['name' => 'El campo nombre debe tener al menos 2 caracteres.']);
        $this->post('/operators', ['name' => ' '])
            ->assertSessionHasErrors('name');
        $this->assertDatabaseCount('operators', 0);
    }

    public function test_store_duplicate_active_name_returns_validation_error(): void
    {
        $this->post('/operators', ['name' => 'Marta Soler'])->assertSessionHasNoErrors();
        $this->post('/operators', ['name' => 'marta soler'])
            ->assertSessionHasErrors(['name' => '«Marta Soler» ya está dado de alta como operador.']);
        $this->assertDatabaseCount('operators', 1);
    }

    public function test_deactivate_hides_operator_from_selector_but_keeps_traceability(): void
    {
        $this->post('/operators', ['name' => 'Marta Soler'])->assertSessionHasNoErrors();
        $id = OperatorModel::query()->where('name', 'Marta Soler')->firstOrFail()->id;

        $this->patch("/operators/{$id}/deactivate")->assertSessionHasNoErrors();
        $this->assertFalse(OperatorModel::query()->find($id)->active);

        // Dado de baja: ya no sale en el selector…
        $this->get('/incidents')
            ->assertInertia(fn (Assert $p) => $p
                ->where('authors', fn ($authors) => ! collect($authors)->contains('Marta Soler')));

        // …pero darlo de alta de nuevo lo reactiva sin duplicar.
        $this->post('/operators', ['name' => 'Marta Soler'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('operators', 1);
        $this->assertTrue(OperatorModel::query()->find($id)->active);
        $this->get('/incidents')
            ->assertInertia(fn (Assert $p) => $p
                ->where('authors', fn ($authors) => collect($authors)->first() === 'Marta Soler'));
    }

    public function test_concurrent_duplicate_insert_is_a_domain_error_not_a_500(): void
    {
        $repo = $this->app->make(OperatorRepository::class);
        $at = new \DateTimeImmutable;
        $repo->save(Operator::register($repo->nextIdentity(), 'Marta Soler', $at));

        // Simula la carrera: el segundo alta no vio al primero en findByName().
        $this->expectException(OperatorAlreadyRegistered::class);
        $repo->save(Operator::register($repo->nextIdentity(), 'Marta Soler', $at));
    }

    public function test_deactivate_unknown_operator_404(): void
    {
        $this->patch('/operators/02b22b72-0000-7000-8000-000000000000/deactivate')->assertNotFound();
    }
}
