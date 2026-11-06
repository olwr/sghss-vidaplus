<?php

namespace App\Shared\Auditoria;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait Auditavel
{
    use LogsActivity;

    /** Campos que nunca entram no log (LGPD: registra-se QUE a entidade mudou, não o valor sensível). */
    protected function camposNaoAuditaveis(): array
    {
        return ['password', 'remember_token', 'cpf_hash'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(array_merge(
                $this->camposNaoAuditaveis(),
                array_filter(array_keys($this->getAttributes()), fn ($c) => str_ends_with($c, '_criptografado')),
            ))
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName(class_basename($this));
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $activity->properties = collect($activity->properties)->merge([
            'ip' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
        ]);
    }
}
