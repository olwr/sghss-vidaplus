<?php

namespace App\Domains\Telemedicina\Models;

use App\Domains\Seguranca\Models\Usuario;
use App\Shared\Enums\EventoSessaoTelemedicina;
use Database\Factories\LogSessaoTelemedicinaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Append-only: sem timestamps do Eloquent (usa ocorrido_em) e sem Auditavel.
class LogSessaoTelemedicina extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'telemedicina_logs_sessao';

    protected $fillable = ['sala_id', 'usuario_id', 'evento', 'ip', 'user_agent', 'detalhes', 'ocorrido_em'];

    protected static function newFactory(): LogSessaoTelemedicinaFactory
    {
        return LogSessaoTelemedicinaFactory::new();
    }

    protected function casts(): array
    {
        return [
            'evento' => EventoSessaoTelemedicina::class,
            'detalhes' => 'array',
            'ocorrido_em' => 'datetime',
        ];
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(SalaTelemedicina::class, 'sala_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
