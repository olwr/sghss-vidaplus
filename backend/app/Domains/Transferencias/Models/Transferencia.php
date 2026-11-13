<?php

namespace App\Domains\Transferencias\Models;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\StatusTransferencia;
use App\Shared\Enums\TipoTransferencia;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\TransferenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Sem BelongsToUnidade: a transferência cruza unidades por definição (Policy: administrador/secretário).
class Transferencia extends Model
{
    use Auditavel;
    use HasFactory;
    use HasUuid;

    protected $table = 'transferencias';

    // mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'status' => 'pendente',
    ];

    // status, solicitante_id e efetivada_em são definidos pelo TransferenciaService
    protected $fillable = [
        'tipo', 'paciente_id', 'profissional_id', 'unidade_origem_id', 'unidade_destino_id', 'motivo',
    ];

    protected static function newFactory(): TransferenciaFactory
    {
        return TransferenciaFactory::new();
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoTransferencia::class,
            'status' => StatusTransferencia::class,
            'efetivada_em' => 'datetime',
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

    public function origem(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unidade_origem_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unidade_destino_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }
}
