<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Los tests HTTP no dependen de que se haya ejecutado `npm run build`.
        $this->withoutVite();
    }
}
