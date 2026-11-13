<?php

namespace App\Domains\Internacoes\Models;

use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Enums\StatusLeito;
use App\Shared\Enums\TipoLeito;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\LeitoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leito extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;

    protected $table = 'leitos';

    // Mesmo default do banco: o atributo já nasce preenchido (sem precisar de refresh())
    protected $attributes = [
        'status' => 'disponivel'
    ];

    // status é mantido pelo InternacaoService sob lock de linha
    protected $fillable = ['unidade_id', 'codigo', 'setor', 'tipo', 'valor_diaria'];

    protected static function newFactory(): LeitoFactory
    {
        return LeitoFactory::new();
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoLeito::class,
            'status' => StatusLeito::class,
            'valor_diaria' => 'decimal:2'
        ];
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function internacoes(): HasMany
    {
        return $this->hasMany(Internacao::class);
    }
}
