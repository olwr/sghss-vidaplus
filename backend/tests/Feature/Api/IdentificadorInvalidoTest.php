<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ComApi;
use Tests\TestCase;

/** Achado do teste manual: um id fora do formato uuid chegava ao PostgreSQL e virava 500; agora é 404. */
class IdentificadorInvalidoTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    public static function rotas(): array
    {
        return [
            'unidade' => ['/api/v1/unidades/nao-e-uuid'],
            'paciente' => ['/api/v1/pacientes/123'],
            'agendamento' => ['/api/v1/agendamentos/abc'],
            'profissional' => ['/api/v1/profissionais/oliver'],
        ];
    }

    #[DataProvider('rotas')]
    public function test_id_fora_do_formato_uuid_responde_404_e_nao_500(string $url): void
    {
        $r = $this->como($this->admin())->getJson($url);

        $r->assertStatus(404)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 404)
            ->assertJsonPath('detail', 'Identificador inválido.');
        $this->assertStringNotContainsString('SQLSTATE', $r->getContent());
    }

    public function test_uuid_valido_porem_inexistente_tambem_responde_404(): void
    {
        $this->como($this->admin())
            ->getJson('/api/v1/unidades/018f3a2e-0000-7000-8000-000000000000')
            ->assertStatus(404);
    }
}
