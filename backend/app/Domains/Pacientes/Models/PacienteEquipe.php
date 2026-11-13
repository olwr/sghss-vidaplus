<?php

namespace App\Domains\Pacientes\Models;

use App\Domains\Profissionais\Models\Equipe;
use Database\Factories\PacienteEquipeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PacienteEquipe extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'paciente_equipes';

    protected $fillable = ['paciente_id', 'equipe_id', 'inicio_em', 'fim_em'];

    protected static function newFactory(): PacienteEquipeFactory
    {
        return PacienteEquipeFactory::new();
    }

    protected function casts(): array
    {
        return [
            'inicio_em' => 'date',
            'fim_em' => 'date'
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class);
    }
}
