<?php

namespace App\Domains\Prontuarios\Models;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\StatusExame;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\ExameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exame extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'exames';

    // Mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'status' => 'solicitado',
    ];

    protected $fillable = [
        'paciente_id', 'solicitante_id', 'unidade_id', 'agendamento_id', 'registro_clinico_id',
        'nome', 'instrucoes', 'valor'
    ];

    protected static function newFactory(): ExameFactory
    {
        return ExameFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => StatusExame::class,
            'valor' => 'decimal:2',
            'solicitado_em' => 'datetime',
            'realizado_em' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'solicitante_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function registroClinico(): BelongsTo
    {
        return $this->belongsTo(RegistroClinico::class);
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoExame::class);
    }

    /** Regra do DBML: com agendamento, prevalece o valor do Agendamento; sem ele, o do Exame. */
    public function valorEfetivo(): ?string
    {
        return $this->agendamento->valor ?? $this->valor;
    }
}
