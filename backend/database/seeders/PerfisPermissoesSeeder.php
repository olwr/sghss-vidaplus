<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PerfisPermissoesSeeder extends Seeder
{
    private const GUARD = 'web';

    /** Catálogo: recurso => ações. Permissão = "recurso.acao". */
    private const PERMISSOES = [
        'pacientes' => ['listar', 'ver', 'criar', 'editar', 'excluir', 'anonimizar'],
        'prontuarios' => ['ver', 'editar'],
        'registros_clinicos' => ['ver', 'criar', 'editar'],
        'agendas' => ['ver', 'gerenciar'],
        'agendamentos' => ['listar', 'ver', 'criar', 'remarcar', 'cancelar'],
        'receitas' => ['ver', 'emitir', 'cancelar'],
        'exames' => ['ver', 'solicitar', 'registrar_resultado'],
        'telemedicina' => ['entrar', 'agendar', 'conduzir', 'monitorar'],
        'unidades' => ['ver', 'gerenciar'],
        'profissionais' => ['ver', 'gerenciar'],
        'equipes' => ['ver', 'gerenciar'],
        'internacoes' => ['ver', 'solicitar', 'gerenciar'],
        'leitos' => ['ver', 'gerenciar'],
        'suprimentos' => ['ver', 'gerenciar'],
        'transferencias' => ['registrar', 'gerenciar'],
        'usuarios' => ['gerenciar'],
        'perfis' => ['gerenciar'],
        'auditoria' => ['ver'],
        'lgpd' => ['exportar', 'anonimizar', 'consentimentos'],
        'relatorios' => ['ver', 'exportar'],
    ];

    /** Matriz perfil => permissões ("recurso.*" = todas as ações do recurso). Publique no PDF. */
    private const PERFIS = [
        'paciente' => [
            'pacientes.ver', 'agendamentos.listar', 'agendamentos.ver', 'agendamentos.criar', 'agendamentos.cancelar',
            'prontuarios.ver', 'receitas.ver', 'exames.ver', 'telemedicina.entrar',
            'lgpd.exportar', 'lgpd.consentimentos',
        ],
        'secretario' => [
            'pacientes.listar', 'pacientes.ver', 'pacientes.criar', 'pacientes.editar',
            'agendas.ver', 'agendamentos.*', 'telemedicina.agendar',
            'unidades.ver', 'profissionais.ver', 'equipes.ver',
            'internacoes.ver', 'leitos.ver', 'suprimentos.ver', 'transferencias.registrar',
        ],
        'profissional_saude' => [
            'pacientes.listar', 'pacientes.ver', 'prontuarios.*', 'registros_clinicos.*',
            'agendas.*', 'agendamentos.listar', 'agendamentos.ver', 'agendamentos.remarcar', 'agendamentos.cancelar',
            'receitas.*', 'exames.*', 'telemedicina.conduzir',
            'unidades.ver', 'profissionais.ver', 'equipes.ver',
            'internacoes.ver', 'internacoes.solicitar', 'leitos.ver',
        ],
        // administrador NÃO tem permissões clínicas (prontuários, registros, receitas, exames)
        'administrador' => [
            'pacientes.*', 'agendas.*', 'agendamentos.*', 'telemedicina.agendar', 'telemedicina.monitorar',
            'unidades.*', 'profissionais.*', 'equipes.*', 'internacoes.*', 'leitos.*', 'suprimentos.*',
            'transferencias.*', 'usuarios.*', 'perfis.*', 'auditoria.*', 'lgpd.*', 'relatorios.*',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSOES as $recurso => $acoes) {
            foreach ($acoes as $acao) {
                Permission::findOrCreate("{$recurso}.{$acao}", self::GUARD);
            }
        }

        foreach (self::PERFIS as $perfil => $itens) {
            Role::findOrCreate($perfil, self::GUARD)->syncPermissions($this->expandir($itens));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return list<string> */
    private function expandir(array $itens): array
    {
        $permissoes = [];
        foreach ($itens as $item) {
            if (str_ends_with($item, '.*')) {
                $recurso = substr($item, 0, -2);
                foreach (self::PERMISSOES[$recurso] as $acao) {
                    $permissoes[] = "{$recurso}.{$acao}";
                }
            } else {
                $permissoes[] = $item;
            }
        }

        return array_values(array_unique($permissoes));
    }
}
