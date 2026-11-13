<?php

namespace App\Domains\Prontuarios\Models;

use Database\Factories\ReceitaItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceitaItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'receita_itens';

    protected $fillable = ['receita_id', 'medicamento', 'dosagem', 'posologia', 'duracao', 'quantidade'];

    protected static function newFactory(): ReceitaItemFactory
    {
        return ReceitaItemFactory::new();
    }

    protected function casts(): array
    {
        return ['quantidade' => 'integer'];
    }

    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class);
    }
}
