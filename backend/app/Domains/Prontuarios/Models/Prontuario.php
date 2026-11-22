<?php

namespace App\Domains\Prontuarios\Models;

use App\Domains\Pacientes\Models\Paciente;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\ProntuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// Sem BelongsToUnidade (não tem unidade_id): a Policy restringe via paciente (e o secretário nunca lê).
class Prontuario extends Model
{
    use Auditavel;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'prontuarios';

    protected $fillable = ['paciente_id', 'tipo_sanguineo', 'alergias_criptografado', 'observacoes_gerais_criptografado'];

    protected static function newFactory(): ProntuarioFactory
    {
        return ProntuarioFactory::new();
    }

    protected function casts(): array
    {
        return [
            'alergias_criptografado' => 'encrypted',
            'observacoes_gerais_criptografado' => 'encrypted',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroClinico::class)->orderByDesc('registrado_em');
    }
}
