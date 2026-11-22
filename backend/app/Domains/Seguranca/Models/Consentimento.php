<?php

namespace App\Domains\Seguranca\Models;

use App\Domains\Pacientes\Models\Paciente;
use App\Shared\Auditoria\Auditavel;
use Database\Factories\ConsentimentoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consentimento extends Model
{
    use Auditavel;
    use HasFactory;

    protected $table = 'consentimentos';

    protected $fillable = ['paciente_id', 'finalidade', 'versao_termo', 'concedido_em', 'revogado_em', 'ip_origem'];

    protected static function newFactory(): ConsentimentoFactory
    {
        return ConsentimentoFactory::new();
    }

    protected function casts(): array
    {
        return [
            'concedido_em' => 'datetime',
            'revogado_em' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function estaVigente(): bool
    {
        return $this->revogado_em === null;
    }
}
