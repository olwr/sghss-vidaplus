<?php

namespace App\Domains\Profissionais\Models;

use App\Domains\Agendamentos\Models\Agenda;
use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\TipoProfissional;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use App\Shared\Models\Concerns\TemCpfProtegido;
use Database\Factories\ProfissionalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profissional extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use TemCpfProtegido;

    protected $table = 'profissionais';

    protected $fillable = [
        'usuario_id', 'unidade_id', 'tipo', 'especialidade',
        'conselho_sigla', 'conselho_numero', 'conselho_uf', 'ativo',
    ];

    protected static function newFactory(): ProfissionalFactory
    {
        return ProfissionalFactory::new();
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoProfissional::class,
            'ativo' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function equipes(): BelongsToMany
    {
        return $this->belongsToMany(Equipe::class, 'equipe_profissionais')
            ->withPivot('funcao', 'created_at');
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }
}
