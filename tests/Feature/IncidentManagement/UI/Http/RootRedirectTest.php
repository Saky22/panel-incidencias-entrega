<?php

namespace Tests\Feature\IncidentManagement\UI\Http;

use Tests\TestCase;

final class RootRedirectTest extends TestCase
{
    public function test_root_redirects_to_incidents(): void
    {
        $this->get('/')->assertRedirect('/incidents');
    }
}
