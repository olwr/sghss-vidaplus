<?php

namespace App\Domains\Prontuarios\Models;

use App\Domains\Profissionais\Models\Profissional;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\ResultadoExameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// Sem BelongsToUnidade: o acesso é decidido pela Policy via exame -> paciente.
class ResultadoExame extends Model
{
    use Auditavel;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'resultados_exames';

    protected $fillable = [
        'exame_id', 'registrado_por_id', 'resumo_criptografado',
        'arquivo_path', 'arquivo_nome_original', 'arquivo_mime', 'arquivo_tamanho', 'disponibilizado_em',
    ];

    protected static function newFactory(): ResultadoExameFactory
    {
        return ResultadoExameFactory::new();
    }

    protected function casts(): array
    {
        return [
            'resumo_criptografado' => 'encrypted',
            'arquivo_tamanho' => 'integer',
            'disponibilizado_em' => 'datetime',
        ];
    }

    public function exame(): BelongsTo
    {
        return $this->belongsTo(Exame::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'registrado_por_id');
    }

    public function estaDisponivel(): bool
    {
        return $this->disponibilizado_em !== null && now()->gte($this->disponibilizado_em);
    }
}
