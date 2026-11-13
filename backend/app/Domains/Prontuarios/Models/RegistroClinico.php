<?php

namespace App\Domains\Prontuarios\Models;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\RegistroClinicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistroClinico extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'registros_clinicos';

    protected $fillable = [
        'prontuario_id', 'profissional_id', 'unidade_id', 'agendamento_id',
        'queixa_principal_criptografado', 'diagnostico_criptografado',
        'conduta_criptografado', 'observacoes_criptografado', 'registrado_em',
    ];

    protected static function newFactory(): RegistroClinicoFactory
    {
        return RegistroClinicoFactory::new();
    }

    protected function casts(): array
    {
        return [
            'queixa_principal_criptografado' => 'encrypted',
            'diagnostico_criptografado' => 'encrypted',
            'conduta_criptografado' => 'encrypted',
            'observacoes_criptografado' => 'encrypted',
            'registrado_em' => 'datetime',
        ];
    }

    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }
}
