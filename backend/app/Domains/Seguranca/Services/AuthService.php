<?php

namespace App\Domains\Seguranca\Services;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Http\Resources\UsuarioResource;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Exceptions\ConflictException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /** Hash gerado uma vez por processo: iguala o tempo de resposta de "usuário inexistente" e "senha errada". */
    private static ?string $hashFicticio = null;

    public function __construct(private readonly ConsentimentoService $consentimentos)
    {
    }

    /** @return array{token: string, tipo: string, expira_em: string, usuario: UsuarioResource} */
    public function autenticar(string $email, string $senha): array
    {
        $email = mb_strtolower(trim($email));
        $usuario = Usuario::query()->where('email', $email)->first();

        $hash = $usuario?->password ?? (self::$hashFicticio ??= Hash::make(Str::random(40)));
        $ok = Hash::check($senha, $hash) && $usuario?->ativo === true;

        if (! $ok) {
            activity('auth')->event('login_falhou')
                ->withProperties(['email_sha256' => hash('sha256', $email), 'ip' => request()->ip()])
                ->log('Falha de login');

            throw new AuthenticationException('Credenciais inválidas.');   // mesma mensagem para qualquer causa
        }

        $usuario->forceFill(['ultimo_login_em' => now()])->save();

        $expira = now()->addMinutes((int) config('sanctum.expiration', 60));
        $perfil = (string) $usuario->getRoleNames()->first();
        $token = $usuario->createToken('api', ['perfil:'.$perfil], $expira);

        activity('auth')->causedBy($usuario)->event('login')->log('Login realizado');

        return [
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'expira_em' => $expira->toIso8601String(),
            'usuario' => UsuarioResource::make($usuario->load('unidade')),
        ];
    }

    /** Autocadastro do paciente: Usuario + Paciente + Consentimento na mesma transação. */
    public function registrar(array $dados, ?string $ip): Usuario
    {
        return DB::transaction(function () use ($dados, $ip) {
            if (Paciente::withoutGlobalScopes()->porCpf($dados['cpf'])->exists()) {
                throw new ConflictException('Já existe paciente com este CPF.');
            }

            $unidade = Unidade::query()->where('uuid', $dados['unidade_id'])->firstOrFail();

            $usuario = Usuario::create([
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'] ?? null,
                'password' => $dados['password'],
                'ativo' => true,
            ]);
            $usuario->assignRole('paciente');

            $paciente = Paciente::create([
                'usuario_id' => $usuario->id,
                'unidade_id' => $unidade->id,
                'nome' => $dados['nome'],
                'cpf' => $dados['cpf'],
                'data_nascimento' => $dados['data_nascimento'],
                'sexo' => $dados['sexo'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'] ?? null,
            ]);

            $this->consentimentos->conceder($paciente, 'atendimento_saude', $ip);

            return $usuario;
        });
    }

    public function sair(Usuario $usuario): void
    {
        $usuario->currentAccessToken()?->delete();
        activity('auth')->causedBy($usuario)->event('logout')->log('Logout realizado');
    }
}
