<?php

namespace Tests\Feature\IncidentManagement\UI\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\IncidentLogModel;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\Model\IncidentModel;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders\InconsistentDataSeeder;
use Tests\TestCase;

final class IncidentHttpTest extends TestCase
{
    use RefreshDatabase;

    private function create(array $override = []): IncidentModel
    {
        $data = array_merge([
            'title' => 'TPV caja 2 bloqueado',
            'description' => 'No arranca tras actualización',
            'priority' => 'high',
            'requester_name' => 'Tienda Sants',
        ], $override);
        $this->post('/incidents', $data)->assertSessionHasNoErrors();

        return IncidentModel::query()->where('title', $data['title'])->firstOrFail();
    }

    public function test_create_incident_persists_with_creation_log(): void
    {
        $incident = $this->create();

        $this->assertSame('open', $incident->status->value);
        $this->assertDatabaseHas('incident_logs', [
            'incident_id' => $incident->id, 'action' => 'created', 'old_value' => null, 'new_value' => 'open',
        ]);
    }

    public function test_create_validation_errors(): void
    {
        $this->post('/incidents', ['title' => 'abc', 'priority' => 'urgent'])
            ->assertSessionHasErrors(['title', 'description', 'priority', 'requester_name'])
            ->assertSessionHasErrors(['title' => 'El campo título debe tener al menos 5 caracteres.']);
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_index_lists_filters_and_counts(): void
    {
        $this->create(['title' => 'Impresora tickets', 'priority' => 'low']);
        $b = $this->create(['title' => 'Datáfono sin red', 'priority' => 'critical']);
        $this->patch("/incidents/{$b->id}/status", ['status' => 'under_review', 'user_name' => 'ana']);

        $this->get('/incidents?status=under_review')
            ->assertInertia(fn (Assert $p) => $p
                ->component('Incidents/Index')
                ->has('incidents.data', 1)
                ->where('incidents.data.0.title', 'Datáfono sin red')
                ->where('counts.open', 1)
                ->where('counts.under_review', 1)
                ->where('filters.status', 'under_review'));

        $this->get('/incidents?search=impresora')
            ->assertInertia(fn (Assert $p) => $p->has('incidents.data', 1)->where('incidents.data.0.title', 'Impresora tickets'));
    }

    public function test_valid_status_change_is_logged_with_context(): void
    {
        $i = $this->create();
        $this->patch("/incidents/{$i->id}/status", ['status' => 'under_review', 'user_name' => 'ana'])
            ->assertSessionHasNoErrors();
        $this->patch("/incidents/{$i->id}/status", ['status' => 'blocked', 'user_name' => 'ana', 'reason' => 'Proveedor'])
            ->assertSessionHasNoErrors();

        $this->assertSame('blocked', $i->fresh()->status->value);
        $log = IncidentLogModel::query()->where('incident_id', $i->id)->orderByDesc('created_at')->first();
        $this->assertSame(['under_review', 'blocked'], [$log->old_value, $log->new_value]);
        $this->assertSame('Proveedor', $log->metadata['reason']);
        $this->assertArrayHasKey('ip', $log->metadata);
    }

    public function test_invalid_transition_returns_validation_error_and_writes_nothing(): void
    {
        $i = $this->create();
        $this->patch("/incidents/{$i->id}/status", ['status' => 'resolved', 'user_name' => 'ana'])
            ->assertSessionHasErrors('status');

        $this->assertSame('open', $i->fresh()->status->value);
        $this->assertSame(1, IncidentLogModel::query()->where('incident_id', $i->id)->count());
    }

    public function test_block_requires_reason(): void
    {
        $i = $this->create();
        $this->patch("/incidents/{$i->id}/status", ['status' => 'under_review', 'user_name' => 'ana']);
        $this->patch("/incidents/{$i->id}/status", ['status' => 'blocked', 'user_name' => 'ana'])
            ->assertSessionHasErrors('reason');
        $this->assertSame('under_review', $i->fresh()->status->value);
    }

    public function test_unknown_incident_404(): void
    {
        $this->patch('/incidents/01a11a71-0000-7000-8000-000000000000/status', ['status' => 'under_review', 'user_name' => 'x'])
            ->assertNotFound();
    }

    public function test_comment_and_history_timeline(): void
    {
        $i = $this->create();
        $this->post("/incidents/{$i->id}/comments", ['author_name' => 'luis', 'body' => 'Reinicio en remoto'])
            ->assertSessionHasNoErrors();
        $this->post("/incidents/{$i->id}/comments", ['author_name' => 'luis', 'body' => ''])
            ->assertSessionHasErrors('body');
        $this->patch("/incidents/{$i->id}/status", ['status' => 'under_review', 'user_name' => 'ana']);

        $this->getJson("/incidents/{$i->id}/history")
            ->assertOk()
            ->assertJsonPath('incident.status.value', 'under_review')
            ->assertJsonCount(3, 'timeline')
            ->assertJsonPath('timeline.0.action', 'created')
            ->assertJsonPath('timeline.1.type', 'comment')
            ->assertJsonPath('timeline.2.new_value', 'under_review');
    }

    public function test_reopen_is_logged_explicitly_and_shown_in_history(): void
    {
        $i = $this->create();
        foreach (['under_review', 'resolved'] as $status) {
            $this->patch("/incidents/{$i->id}/status", ['status' => $status, 'user_name' => 'ana']);
        }
        $this->patch("/incidents/{$i->id}/status", ['status' => 'open', 'user_name' => 'ana'])
            ->assertSessionHasErrors(['reason' => 'Pasar de «Resuelta» a «Abierta» requiere indicar un motivo.']);
        $this->patch("/incidents/{$i->id}/status", ['status' => 'open', 'user_name' => 'ana', 'reason' => 'Vuelve a fallar'])
            ->assertSessionHasNoErrors();

        $this->getJson("/incidents/{$i->id}/history")
            ->assertJsonPath('timeline.3.action', 'reopened')
            ->assertJsonPath('timeline.3.action_label', 'Reapertura')
            ->assertJsonPath('timeline.3.reason', 'Vuelve a fallar')
            ->assertJsonPath('stats.status_changes', 3);
    }

    public function test_incidents_flagged_by_diagnostics_show_review_badge(): void
    {
        $this->seed(InconsistentDataSeeder::class);
        $this->artisan('incidents:diagnose', ['--fix' => true, '--no-interaction' => true]);

        $this->get('/incidents?search=SIM-4')
            ->assertInertia(fn (Assert $p) => $p
                ->where('incidents.data.0.needs_review', true)
                ->where('incidents.data.0.review_flags.0.label', 'Log incoherente (cadena rota)'));
        $this->get('/incidents?search=SIM-1')
            ->assertInertia(fn (Assert $p) => $p->where('incidents.data.0.needs_review', false));
    }

    public function test_index_exposes_known_authors_for_the_selector(): void
    {
        $i = $this->create(['requester_name' => 'Tienda Sants']);
        $this->post("/incidents/{$i->id}/comments", ['author_name' => 'luis', 'body' => 'Reinicio en remoto']);
        $this->patch("/incidents/{$i->id}/status", ['status' => 'under_review', 'user_name' => 'ana']);

        $this->get('/incidents')
            ->assertInertia(fn (Assert $p) => $p
                ->has('authors')
                ->where('authors', fn ($authors) => collect($authors)->intersect(['ana', 'luis', 'Tienda Sants'])->count() === 3));
    }

    public function test_logs_are_immutable_through_eloquent(): void
    {
        $i = $this->create();
        $log = IncidentLogModel::query()->where('incident_id', $i->id)->first();

        $this->expectException(\LogicException::class);
        $log->update(['new_value' => 'resolved']);
    }
}
