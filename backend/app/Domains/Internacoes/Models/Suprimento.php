<?php

namespace App\Domains\Internacoes\Models;

use App\Domains\Unidades\Models\Unidade;
use App\Shared\Auditoria\Auditavel;
use App\Shared\Models\Concerns\BelongsToUnidade;
use App\Shared\Models\Concerns\HasUuid;
use Database\Factories\SuprimentoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suprimento extends Model
{
    use Auditavel;
    use BelongsToUnidade;
    use HasFactory;
    use HasUuid;

    protected $table = 'suprimentos';

    protected $fillable = ['unidade_id', 'nome', 'codigo', 'unidade_medida', 'quantidade_atual', 'quantidade_minima'];

    protected static function newFactory(): SuprimentoFactory
    {
        return SuprimentoFactory::new();
    }

    protected function casts(): array
    {
        return [
            'quantidade_atual' => 'integer',
            'quantidade_minima' => 'integer',
        ];
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function abaixoDoMinimo(): bool
    {
        return $this->quantidade_atual < $this->quantidade_minima;
    }
}
