<?php

namespace Database\Seeders;

use App\Domains\Agendamentos\Models\Agenda;
use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Internacoes\Models\Leito;
use App\Domains\Internacoes\Models\Suprimento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Pacientes\Models\PacienteEquipe;
use App\Domains\Profissionais\Models\Equipe;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Prontuarios\Models\Exame;
use App\Domains\Prontuarios\Models\Prontuario;
use App\Domains\Prontuarios\Models\Receita;
use App\Domains\Prontuarios\Models\RegistroClinico;
use App\Domains\Prontuarios\Models\ResultadoExame;
use App\Domains\Seguranca\Models\Consentimento;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Telemedicina\Models\SalaTelemedicina;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\StatusAgendamento;
use App\Shared\Enums\StatusExame;
use App\Shared\Enums\TipoLeito;
use App\Shared\Enums\TipoUnidade;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

/** Personas do estudo de caso (Ana Maria, Renato, Camila, Alan). NUNCA em produção. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder não pode rodar em produção.');
        }

        $senha = config('sghss.demo_password');
        if (blank($senha)) {
            throw new RuntimeException('Defina DEMO_PASSWORD no .env (e rode config:clear) para popular os dados de demonstração.');
        }

        // --- 4 unidades (RF002) ---
        $hospital = Unidade::factory()->tipo(TipoUnidade::Hospital)->create(['nome' => 'VidaPlus Hospital Central', 'cidade' => 'Pelotas', 'uf' => 'RS']);
        $clinica = Unidade::factory()->tipo(TipoUnidade::Clinica)->create(['nome' => 'VidaPlus Clínica Jardins', 'cidade' => 'Pelotas', 'uf' => 'RS']);
        Unidade::factory()->tipo(TipoUnidade::Laboratorio)->create(['nome' => 'VidaPlus Laboratório Diagnósticos', 'cidade' => 'Pelotas', 'uf' => 'RS']);
        $homeCare = Unidade::factory()->tipo(TipoUnidade::HomeCare)->create(['nome' => 'VidaPlus Home Care', 'cidade' => 'Pelotas', 'uf' => 'RS']);

        // --- personas ---
        Usuario::factory()->administrador()->create([
            'nome' => 'Alan Souza', 'email' => 'alan@vidaplus.test', 'password' => $senha,
        ]);
        Usuario::factory()->secretario()->create([
            'unidade_id' => $hospital->id, 'nome' => 'Renato Lima', 'email' => 'renato@vidaplus.test', 'password' => $senha,
        ]);

        $usuarioCamila = Usuario::factory()->profissionalSaude()->create([
            'unidade_id' => $hospital->id, 'nome' => 'Dra. Camila Rocha', 'email' => 'camila@vidaplus.test', 'password' => $senha,
        ]);
        $camila = Profissional::factory()->create([
            'unidade_id' => $hospital->id, 'usuario_id' => $usuarioCamila->id,
            'especialidade' => 'Clínica Geral', 'conselho_numero' => '123456', 'conselho_uf' => 'SP',
            'cpf' => '529.982.247-25',   // CPF de teste válido (não pertence a ninguém real)
        ]);

        $usuarioAna = Usuario::factory()->comPerfilPaciente()->create([
            'unidade_id' => $hospital->id, 'nome' => 'Ana Maria Pereira', 'email' => 'ana@vidaplus.test', 'password' => $senha,
        ]);
        $ana = Paciente::factory()->comAcessibilidade()->create([
            'unidade_id' => $hospital->id, 'usuario_id' => $usuarioAna->id, 'nome' => 'Ana Maria Pereira',
            'cpf' => '111.444.777-35', 'data_nascimento' => '1958-03-12',
        ]);
        Consentimento::factory()->create(['paciente_id' => $ana->id]);

        // --- agenda da Camila (seg a sex, 08h-12h, 30 min) ---
        foreach (range(1, 5) as $dia) {
            Agenda::factory()->create(['profissional_id' => $camila->id, 'unidade_id' => $hospital->id, 'dia_semana' => $dia]);
        }

        // --- agendamentos da Ana (sem sobreposição: a exclusion constraint valida) ---
        $segunda = Carbon::now()->next(Carbon::MONDAY)->setTime(9, 0);
        $terca = Carbon::now()->next(Carbon::TUESDAY)->setTime(10, 0);

        Agendamento::factory()->em($segunda)->create([
            'paciente_id' => $ana->id, 'profissional_id' => $camila->id, 'unidade_id' => $hospital->id,
            'criado_por_id' => $usuarioAna->id, 'motivo' => 'Consulta de rotina',
        ]);
        $tele = Agendamento::factory()->telemedicina()->em($terca)->create([
            'paciente_id' => $ana->id, 'profissional_id' => $camila->id, 'unidade_id' => $hospital->id,
            'criado_por_id' => $usuarioAna->id, 'motivo' => 'Retorno por telemedicina',
        ]);
        SalaTelemedicina::factory()->create([
            'agendamento_id' => $tele->id,
            'valida_a_partir_de' => $terca->copy()->subMinutes(15),
            'valida_ate' => $terca->copy()->addMinutes(45),
        ]);

        // --- histórico clínico (consulta concluída há 7 dias) ---
        $passada = Carbon::now()->subDays(7)->setTime(9, 0);
        $concluida = Agendamento::factory()->em($passada)->status(StatusAgendamento::Concluido)->create([
            'paciente_id' => $ana->id, 'profissional_id' => $camila->id, 'unidade_id' => $hospital->id,
            'criado_por_id' => $usuarioAna->id, 'motivo' => 'Dor de cabeça recorrente',
        ]);
        $prontuario = Prontuario::factory()->create(['paciente_id' => $ana->id]);
        $registro = RegistroClinico::factory()->create([
            'prontuario_id' => $prontuario->id, 'profissional_id' => $camila->id,
            'unidade_id' => $hospital->id, 'agendamento_id' => $concluida->id,
        ]);
        Receita::factory()->comItens(2)->create([
            'paciente_id' => $ana->id, 'profissional_id' => $camila->id,
            'unidade_id' => $hospital->id, 'registro_clinico_id' => $registro->id,
        ]);
        $exame = Exame::factory()->create([
            'paciente_id' => $ana->id, 'solicitante_id' => $camila->id, 'unidade_id' => $hospital->id,
            'registro_clinico_id' => $registro->id, 'nome' => 'Hemograma completo',
        ]);
        ResultadoExame::factory()->disponivel()->create(['exame_id' => $exame->id, 'registrado_por_id' => $camila->id]);
        $exame->forceFill(['status' => StatusExame::ResultadoDisponivel, 'realizado_em' => now()->subDay()])->save();

        // --- massa de dados para listagens, filtros e relatórios ---
        Paciente::factory()->count(10)->create(['unidade_id' => $hospital->id]);
        Paciente::factory()->count(5)->create(['unidade_id' => $clinica->id]);

        // --- internações e administração hospitalar (RF009) ---
        foreach ([TipoLeito::Enfermaria, TipoLeito::Enfermaria, TipoLeito::Apartamento, TipoLeito::Uti, TipoLeito::Uti] as $i => $tipo) {
            Leito::factory()->create(['unidade_id' => $hospital->id, 'codigo' => sprintf('L-%03d', $i + 1), 'tipo' => $tipo]);
        }
        Suprimento::factory()->count(3)->create(['unidade_id' => $hospital->id]);
        Suprimento::factory()->abaixoDoMinimo()->create(['unidade_id' => $hospital->id, 'nome' => 'Soro fisiológico 500 ml (alerta)']);

        // --- equipe de home care com paciente vinculado ---
        $equipe = Equipe::factory()->create(['unidade_id' => $homeCare->id, 'nome' => 'Equipe Home Care Zona Sul']);
        $enfermeiros = Profissional::factory()->enfermeiro()->count(2)->create(['unidade_id' => $homeCare->id]);
        $equipe->profissionais()->attach($enfermeiros->pluck('id')->all(), ['funcao' => 'Enfermeiro responsável']);
        PacienteEquipe::factory()->create([
            'paciente_id' => Paciente::factory()->create(['unidade_id' => $homeCare->id])->id,
            'equipe_id' => $equipe->id,
        ]);
    }
}
