<?php

namespace App\Domains\Profissionais\Models;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\EquipeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipe extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;

    protected $table = 'equipes';

    protected $fillable = ['unidade_id', 'nome', 'descricao', 'ativa'];

    protected static function newFactory(): EquipeFactory
    {
        return EquipeFactory::new();
    }

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function profissionais(): BelongsToMany
    {
        return $this->belongsToMany(Profissional::class, 'equipe_profissionais')
            ->withPivot('funcao', 'created_at');
    }

    public function pacientes(): BelongsToMany
    {
        return $this->belongsToMany(Paciente::class, 'paciente_equipes')
            ->withPivot('id', 'inicio_em', 'fim_em');
    }
}
