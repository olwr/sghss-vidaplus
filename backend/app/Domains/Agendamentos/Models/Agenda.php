<?php

namespace App\Domains\Agendamentos\Models;

use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Models\Concerns\BelongsToUnidade;
use Database\Factories\AgendaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Sem uuid no DBML: a API expõe a agenda sempre via /profissionais/{uuid}/agendas.
class Agenda extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;

    protected $table = 'agendas';

    protected $fillable = [
        'profissional_id', 'unidade_id', 'dia_semana', 'hora_inicio', 'hora_fim',
        'duracao_atendimento_min', 'vigencia_inicio', 'vigencia_fim', 'ativa',
    ];

    protected static function newFactory(): AgendaFactory
    {
        return AgendaFactory::new();
    }

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'duracao_atendimento_min' => 'integer',
            'vigencia_inicio' => 'date',
            'vigencia_fim' => 'date',
            'ativa' => 'boolean',
        ];
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }
}
