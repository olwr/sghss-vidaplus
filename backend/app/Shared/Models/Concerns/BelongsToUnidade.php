<?php

namespace App\Shared\Models\Concerns;

use App\Shared\Scopes\UnidadeScope;

trait BelongsToUnidade
{
    protected static function bootBelongsToUnidade(): void
    {
        static::addGlobalScope(new UnidadeScope());
    }
}
