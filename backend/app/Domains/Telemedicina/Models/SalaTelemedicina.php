<?php

namespace App\Domains\Telemedicina\Models;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\StatusSalaTelemedicina;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\SalaTelemedicinaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaTelemedicina extends Model
{
    use Auditavel;
    use HasFactory;
    use HasUuid;

    protected $table = 'telemedicina_salas';

    // mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'status' => 'criada',
    ];

    protected $fillable = ['agendamento_id', 'nome_sala', 'valida_a_partir_de', 'valida_ate'];

    protected static function newFactory(): SalaTelemedicinaFactory
    {
        return SalaTelemedicinaFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => StatusSalaTelemedicina::class,
            'valida_a_partir_de' => 'datetime',
            'valida_ate' => 'datetime',
            'iniciada_em' => 'datetime',
            'encerrada_em' => 'datetime',
        ];
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogSessaoTelemedicina::class, 'sala_id');
    }

    public function dentroDaJanela(): bool
    {
        return now()->between($this->valida_a_partir_de, $this->valida_ate);
    }
}
