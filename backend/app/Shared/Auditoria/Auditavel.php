<?php

namespace App\Shared\Auditoria;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait Auditavel
{
    use LogsActivity;

    /**
     * Campos que nunca entram no log (LGPD: registra-se QUE a entidade mudou, não o valor sensível):
     * tudo que tem cast `encrypted`, tudo que é `hidden` (senha, segredos) e o blind index.
     *
     * @return list<string>
     */
    protected function camposNaoAuditaveis(): array
    {
        $criptografados = array_keys(array_filter(
            $this->getCasts(),
            fn ($cast) => is_string($cast) && str_starts_with($cast, 'encrypted')
        ));

        return array_values(array_unique([...$criptografados, ...$this->getHidden(), 'cpf_hash']));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept($this->camposNaoAuditaveis())
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName(class_basename($this));
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        // colunas criadas pela migration 2026_11_01_000320 (DBML: activity_log.ip_address / user_agent)
        $activity->setAttribute('ip_address', request()?->ip());
        $activity->setAttribute('user_agent', substr((string) request()?->userAgent(), 0, 255));
    }
}
