<?php

use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

// use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /** @var list<string> */
    private array $tabelas = ['activity_log', 'logs_acesso', 'telemedicina_logs_sessao'];

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION bloquear_alteracao_auditoria() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Tabela de auditoria é append-only (%).', TG_OP USING ERRCODE = '42501';
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        foreach ($this->tabelas as $tabela) {
            DB::unprepared("CREATE TRIGGER {$tabela}_append_only BEFORE UPDATE OR DELETE ON {$tabela}
                            FOR EACH ROW EXECUTE FUNCTION bloquear_alteracao_auditoria()");
            DB::unprepared("CREATE TRIGGER {$tabela}_sem_truncate BEFORE TRUNCATE ON {$tabela}
                            FOR EACH STATEMENT EXECUTE FUNCTION bloquear_alteracao_auditoria()");
        }
    }

    public function down(): void
    {
        foreach ($this->tabelas as $tabela) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$tabela}_append_only ON {$tabela}");
            DB::unprepared("DROP TRIGGER IF EXISTS {$tabela}_sem_truncate ON {$tabela}");
        }
        DB::unprepared('DROP FUNCTION IF EXISTS bloquear_alteracao_auditoria()');
    }
};
