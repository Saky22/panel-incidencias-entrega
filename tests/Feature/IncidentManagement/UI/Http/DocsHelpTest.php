<?php

namespace Tests\Feature\IncidentManagement\UI\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class DocsHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_renders_readme_and_architecture(): void
    {
        $this->get('/ayuda')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Help/Index')
                ->where('docs.readme', fn ($html) => str_contains((string) $html, 'Panel operativo'))
                ->where('docs.arquitectura', fn ($html) => str_contains((string) $html, 'Arquitectura'))
                ->has('authors'));
    }

    public function test_links_between_documents_point_to_tabs_not_to_repo_paths(): void
    {
        $this->get('/ayuda')->assertInertia(fn (Assert $p) => $p
            ->where('docs.readme', fn ($html) => ! str_contains((string) $html, 'href="docs/ARQUITECTURA.md"')
                && str_contains((string) $html, 'href="#arquitectura"')));
    }
}
