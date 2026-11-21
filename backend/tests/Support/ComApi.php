<?php

namespace Tests\Support;

use App\Domains\Agendamentos\Models\Agenda;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Seeders\PerfisPermissoesSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/** Preparação comum dos testes de API: perfis reais, e-mail e disco falsos, atores e datas. */
trait ComApi
{
    protected Unidade $unidade;

    protected function prepararApi(): void
    {
        // Cinto e suspensório: o `env_file` do Compose põe QUEUE_CONNECTION/CACHE_STORE=redis e APP_ENV=local no contêiner;
        // se o phpunit.xml não os forçar, os testes passariam a depender do Redis e do .env do desenvolvedor.
        config(['queue.default' => 'sync']);
        Cache::setDefaultDriver('array');

        $this->seed(PerfisPermissoesSeeder::class);   // papéis COM permissões: os testes provam a matriz real
        Mail::fake();
        Storage::fake('s3');
        config([
            'sghss.jitsi.app_id' => 'vidaplus-test', 'sghss.jitsi.app_secret' => 'segredo-de-teste-com-32-caracteres!!',
            'sghss.jitsi.domain' => 'meet.exemplo.test', 'sghss.blind_index_key' => 'chave-de-teste-blind-index-32-bytes!!',
        ]);
        $this->unidade = Unidade::factory()->create();
    }

    protected function como(Usuario $usuario): static
    {
        Sanctum::actingAs($usuario);

        return $this;
    }

    protected function admin(): Usuario
    {
        return Usuario::factory()->administrador()->create();
    }

    protected function secretario(?Unidade $unidade = null): Usuario
    {
        return Usuario::factory()->secretario()->create(['unidade_id' => ($unidade ?? $this->unidade)->id]);
    }

    protected function profissional(?Unidade $unidade = null): Profissional
    {
        return Profissional::factory()->create(['unidade_id' => ($unidade ?? $this->unidade)->id]);
    }

    /** Paciente com login (perfil paciente), na unidade informada. */
    protected function pacienteComAcesso(?Unidade $unidade = null): Paciente
    {
        return Paciente::factory()->comAcesso()->create(['unidade_id' => ($unidade ?? $this->unidade)->id]);
    }

    /** Próxima segunda-feira às $hora:$min no fuso da agenda (dia_semana = 1). */
    protected function proximaSegunda(int $hora, int $min = 0): CarbonImmutable
    {
        return CarbonImmutable::now(config('sghss.fuso_agenda', 'America/Sao_Paulo'))
            ->next(CarbonInterface::MONDAY)->setTime($hora, $min);
    }

    /** Agenda de segunda, 08:00–12:00, blocos de 30 min. */
    protected function agendaDeSegunda(Profissional $profissional): Agenda
    {
        return Agenda::factory()->create([
            'profissional_id' => $profissional->id, 'unidade_id' => $profissional->unidade_id,
            'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fim' => '12:00', 'duracao_atendimento_min' => 30,
            'vigencia_inicio' => now()->subDay()->toDateString(), 'vigencia_fim' => null, 'ativa' => true,
        ]);
    }

    protected function corpoAgendamento(Profissional $p, Paciente $paciente, CarbonImmutable $inicio, array $extra = []): array
    {
        return array_merge([
            'tipo' => 'consulta', 'modalidade' => 'presencial',
            'paciente_id' => $paciente->uuid, 'profissional_id' => $p->uuid, 'unidade_id' => $p->unidade->uuid,
            'inicio_em' => $inicio->utc()->toIso8601String(), 'motivo' => 'Consulta de rotina'
        ], $extra);
    }
}
