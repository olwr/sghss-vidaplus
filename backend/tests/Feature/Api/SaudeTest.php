<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaudeTest extends TestCase
{
    use RefreshDatabase;

    public function test_responde_ok_sem_autenticacao_e_sem_vazar_detalhes(): void
    {
        Storage::fake('s3');

        $this->getJson('/api/v1/saude')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'componentes' => ['banco' => 'ok', 'cache' => 'ok', 'armazenamento' => 'ok'],
            ]);
    }

    public function test_responde_503_quando_o_armazenamento_falha(): void
    {
        Storage::shouldReceive('disk')->with('s3')->andThrow(new \RuntimeException('s3 fora'));

        $this->getJson('/api/v1/saude')
            ->assertStatus(503)
            ->assertJsonPath('status', 'degradado')
            ->assertJsonPath('componentes.armazenamento', 'falha')
            ->assertJsonPath('componentes.banco', 'ok');
    }
}
