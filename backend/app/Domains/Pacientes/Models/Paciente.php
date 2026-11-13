<?php

namespace App\Domains\Pacientes\Models;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Profissionais\Models\Equipe;
use App\Domains\Prontuarios\Models\Exame;
use App\Domains\Prontuarios\Models\Prontuario;
use App\Domains\Prontuarios\Models\Receita;
use App\Domains\Seguranca\Models\Consentimento;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\Sexo;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use App\Shared\Models\Concerns\TemCpfProtegido;
use Database\Factories\PacienteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use TemCpfProtegido;

    protected $table = 'pacientes';

    // Mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'sexo' => 'nao_informado',
    ];

    protected $fillable = [
        'unidade_id', 'usuario_id', 'nome', 'data_nascimento', 'sexo', 'email', 'telefone',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf',
        'preferencias_acessibilidade'
    ];

    protected static function newFactory(): PacienteFactory
    {
        return PacienteFactory::new();
    }

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'sexo' => Sexo::class,
            'preferencias_acessibilidade' => 'array',
            'anonimizado_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function prontuario(): HasOne
    {
        return $this->hasOne(Prontuario::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function receitas(): HasMany
    {
        return $this->hasMany(Receita::class);
    }

    public function exames(): HasMany
    {
        return $this->hasMany(Exame::class);
    }

    public function consentimentos(): HasMany
    {
        return $this->hasMany(Consentimento::class);
    }

    public function equipes(): BelongsToMany
    {
        return $this->belongsToMany(Equipe::class, 'paciente_equipes')
            ->withPivot('id', 'inicio_em', 'fim_em');
    }

    /** Equipe(s) com vínculo ativo (fim_em NULL): "Consultar equipe de home care". */
    public function equipesAtivas(): BelongsToMany
    {
        return $this->equipes()->wherePivotNull('fim_em');
    }
}
