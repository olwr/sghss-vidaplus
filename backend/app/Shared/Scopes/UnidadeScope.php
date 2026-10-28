<?php

// namespace App\Shared\Scopes;

// use App\Domains\Seguranca\Models\Usuario;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Scope;
// use Illuminate\Support\Facades\Auth;

// class UnidadeScope implements Scope
// {
//     public function apply(Builder $builder, Model $model): void
//     {
//         $u = Auth::user();
//         // sem usuário (jobs/CLI), usuário de rede (unidade_id nulo) ou paciente: não filtra aqui
//         // (paciente é restringido por Policy + filtro explícito no repositório)
//         if (! $u instanceof Usuario || $u->unidade_id === null || $u->hasRole('paciente')) {
//             return;
//         }
//         $builder->where($model->qualifyColumn('unidade_id'), $u->unidade_id);
//     }
// }
// comentado pois referencia classes que ainda não existem
