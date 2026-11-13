<?php

namespace App\Domains\Unidades\Models;

use App\Domains\Internacoes\Models\Leito;
use App\Domains\Internacoes\Models\Suprimento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Equipe;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\StatusUnidade;
use App\Shared\Enums\TipoUnidade;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\UnidadeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unidade extends Model
{
    use Auditavel;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'unidades';

    // mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'status' => 'ativa',
    ];

    protected $fillable = [
        'tipo', 'nome', 'cnpj', 'status', 'telefone', 'email',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf',
    ];

    protected static function newFactory(): UnidadeFactory
    {
        return UnidadeFactory::new();
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoUnidade::class,
            'status' => StatusUnidade::class,
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public function equipes(): HasMany
    {
        return $this->hasMany(Equipe::class);
    }

    public function pacientes(): HasMany
    {
        return $this->hasMany(Paciente::class);
    }

    public function profissionais(): HasMany
    {
        return $this->hasMany(Profissional::class);
    }

    public function leitos(): HasMany
    {
        return $this->hasMany(Leito::class);
    }

    public function suprimentos(): HasMany
    {
        return $this->hasMany(Suprimento::class);
    }
}
