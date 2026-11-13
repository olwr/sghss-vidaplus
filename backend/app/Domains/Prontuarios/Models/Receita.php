<?php

namespace App\Domains\Prontuarios\Models;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\StatusReceita;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\ReceitaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receita extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'receitas';

    // Mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'status' => 'emitida',
    ];

    // status, arquivo_path e cancelada_em são definidos pelo Service
    protected $fillable = [
        'paciente_id', 'profissional_id', 'unidade_id', 'registro_clinico_id', 'agendamento_id', 'emitida_em',
    ];

    protected static function newFactory(): ReceitaFactory
    {
        return ReceitaFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => StatusReceita::class,
            'emitida_em' => 'datetime',
            'cancelada_em' => 'datetime',
        ];
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

    public function registroClinico(): BelongsTo
    {
        return $this->belongsTo(RegistroClinico::class);
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ReceitaItem::class);
    }
}
