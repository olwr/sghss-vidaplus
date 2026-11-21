<?php

namespace Tests\Feature\Api;

use App\Domains\Pacientes\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\ComApi;
use Tests\TestCase;

/**
 * CT044 / RNF004: consulta crítica dentro do SLA com volume realista.
 * SLA: 300 ms na máquina de desenvolvimento; o CI (runner compartilhado) usa 1000 ms (variável SLA_MS).
 * Rodar só este grupo: php artisan test --group=desempenho
 */
#[Group('desempenho')]
class DesempenhoTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    private const VOLUME = 3000;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    private function sla(): int
    {
        return (int) (getenv('SLA_MS') ?: 300);
    }

    /** Mede a MEDIANA de 5 chamadas (descarta o ruído da primeira, que aquece cache e conexão). */
    private function medianaMs(callable $chamada): float
    {
        $tempos = [];
        for ($i = 0; $i < 5; $i++) {
            $t = hrtime(true);
            $chamada();
            $tempos[] = (hrtime(true) - $t) / 1e6;
        }
        sort($tempos);

        return $tempos[2];
    }

    public function test_ct044_busca_por_cpf_e_listagem_paginada_cumprem_o_sla_com_volume(): void
    {
        $alvo = Paciente::factory()->create(['unidade_id' => $this->unidade->id, 'cpf' => '52998224725']);
        Paciente::factory()->count(self::VOLUME)->create(['unidade_id' => $this->unidade->id]);
        $sec = $this->secretario();

        $porCpf = $this->medianaMs(fn () => $this->como($sec)->getJson('/api/v1/pacientes?cpf=52998224725')->assertOk());
        $lista = $this->medianaMs(fn () => $this->como($sec)->getJson('/api/v1/pacientes?per_page=15')->assertOk());

        fwrite(STDERR, sprintf("\n[CT044] %d pacientes | busca por CPF: %.0f ms | listagem: %.0f ms | SLA: %d ms\n", self::VOLUME, $porCpf, $lista, $this->sla()));
        $this->assertLessThan($this->sla(), $porCpf, 'busca por CPF acima do SLA');
        $this->assertLessThan($this->sla(), $lista, 'listagem acima do SLA');
        $this->assertSame($alvo->uuid, $this->como($sec)->getJson('/api/v1/pacientes?cpf=52998224725')->json('data.0.id'));
    }
}
