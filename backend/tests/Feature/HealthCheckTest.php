<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_responde_200(): void
    {
        $this->get('/up')->assertOk();
    }
}
