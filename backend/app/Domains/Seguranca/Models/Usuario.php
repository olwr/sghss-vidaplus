<?php

namespace App\Domains\Seguranca\Models;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

// Sem BelongsToUnidade: `unidade_id` é a lotação, e o escopo não pode atuar na autenticação.
class Usuario extends Authenticatable
{
    use Auditavel;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'usuarios';

    // mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'ativo' => true,
    ];

    // `web` existe em config/auth.php; o `sanctum` não é um guard declarado ali. [MODIFICAÇÃO A18]
    protected $guard_name = 'web';

    protected $fillable = ['unidade_id', 'nome', 'email', 'telefone', 'password', 'ativo'];

    protected $hidden = ['password', 'two_factor_secret_criptografado'];

    protected static function newFactory(): UsuarioFactory
    {
        return UsuarioFactory::new();
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'ativo' => 'boolean',
            'email_verificado_em' => 'datetime',
            'two_factor_secret_criptografado' => 'encrypted',
            'two_factor_confirmado_em' => 'datetime',
            'ultimo_login_em' => 'datetime',
        ];
    }

    // a tabela não tem remember_token (autenticação é por token Sanctum)
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class);
    }

    public function profissional(): HasOne
    {
        return $this->hasOne(Profissional::class);
    }
}
