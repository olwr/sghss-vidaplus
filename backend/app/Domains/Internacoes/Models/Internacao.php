<?php

namespace App\Domains\Internacoes\Models;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\StatusInternacao;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\InternacaoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Internacao extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'internacoes';

    // Mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'status' => 'solicitada'
    ];

    // status, leito_id, admitida_em e alta_em são definidos pelo InternacaoService
    protected $fillable = ['paciente_id', 'unidade_id', 'solicitante_id', 'responsavel_id', 'motivo_criptografado'];

    protected static function newFactory(): InternacaoFactory
    {
        return InternacaoFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => StatusInternacao::class,
            'motivo_criptografado' => 'encrypted',
            'solicitada_em' => 'datetime',
            'admitida_em' => 'datetime',
            'alta_em' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function leito(): BelongsTo
    {
        return $this->belongsTo(Leito::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'solicitante_id');
    }

    public function resposavel(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'solicitante_id');
    }
}
