<?php

namespace App\Domains\Seguranca\Models;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\AcaoAcesso;
use App\Shared\Models\Concerns\BelongsToUnidade;
use Database\Factories\LogAcessoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Append-only: sem updated_at, sem soft delete e sem Auditavel (ele já é a própria auditoria).
class LogAcesso extends Model
{
    use BelongsToUnidade;
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'logs_acesso';

    protected $fillable = [
        'usuario_id', 'unidade_id', 'paciente_id', 'acao', 'recurso_tipo', 'recurso_id', 'ip', 'user_agent', 'rota',
    ];

    protected static function newFactory(): LogAcessoFactory
    {
        return LogAcessoFactory::new();
    }

    protected function casts(): array
    {
        return ['acao' => AcaoAcesso::class];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
