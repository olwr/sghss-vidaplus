<?php

namespace App\Domains\Agendamentos\Models;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Prontuarios\Models\Exame;
use App\Domains\Prontuarios\Models\RegistroClinico;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Telemedicina\Models\SalaTelemedicina;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\ModalidadeAgendamento;
use App\Shared\Enums\StatusAgendamento;
use App\Shared\Enums\TipoAgendamento;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\AgendamentoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// Sem SoftDeletes (DBML): o agendamento "some" via status cancelado/remarcado, nunca por exclusão.
class Agendamento extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;

    protected $table = 'agendamentos';

    // Mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'modalidade' => 'presencial',
        'status' => 'agendado'
    ];

    // Só o que o cliente pode enviar. status, criado_por_id, cancelado_*, remarcado_de_id,
    // retorno_de_id, lembrete_enviado_em e chave_idempotencia são definidos pelo Service (anti mass assignment).
    protected $fillable = [
        'tipo', 'modalidade', 'paciente_id', 'profissional_id', 'unidade_id',
        'inicio_em', 'fim_em', 'motivo', 'valor'
    ];

    protected static function newFactory(): AgendamentoFactory
    {
        return AgendamentoFactory::new();
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoAgendamento::class,
            'modalidade' => ModalidadeAgendamento::class,
            'status' => StatusAgendamento::class,
            'inicio_em' => 'datetime',
            'fim_em' => 'datetime',
            'valor' => 'decimal:2',
            'cancelado_em' => 'datetime',
            'lembrete_enviado_em' => 'datetime',
        ];
    }

    /** Espelha o WHERE da exclusion constraint: estes status liberam o horário. */
    public function scopeOcupandoHorario(Builder $consulta): Builder
    {
        return $consulta->whereNotIn('status', [
            StatusAgendamento::Cancelado->value,
            StatusAgendamento::Remarcado->value,
            StatusAgendamento::Faltou->value
        ]);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function retornoDe(): BelongsTo
    {
        return $this->belongsTo(self::class, 'retorno_de_id');
    }

    public function remarcadoDe(): BelongsTo
    {
        return $this->belongsTo(self::class, 'remarcado_de_id');
    }

    public function remarcacoes(): HasMany
    {
        return $this->hasMany(self::class, 'remarcado_de_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por_id');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cancelado_por_id');
    }

    public function exame(): HasOne
    {
        return $this->hasOne(Exame::class);
    }

    public function sala(): HasOne
    {
        return $this->hasOne(SalaTelemedicina::class);
    }

    public function registrosClinicos(): HasMany
    {
        return $this->hasMany(RegistroClinico::class);
    }
}
