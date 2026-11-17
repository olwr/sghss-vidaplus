import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { Perfil } from '@/types/api'

declare module 'vue-router' {
  interface RouteMeta {
    publica?: boolean
    perfis?: Perfil[]
    titulo?: string
  }
}

const PublicLayout = () => import('@/layouts/PublicLayout.vue')
const AppLayout = () => import('@/layouts/AppLayout.vue')

const filhas = (perfis: Perfil[], rotas: RouteRecordRaw[]): RouteRecordRaw[] =>
  rotas.map((r) => ({ ...r, meta: { ...r.meta, perfis } }))

export const rotas: RouteRecordRaw[] = [
  {
    path: '/',
    component: PublicLayout,
    meta: { publica: true },
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('@/pages/LoginPage.vue'),
        meta: { publica: true, titulo: 'Entrar' },
      },
      {
        path: 'registro',
        name: 'registro',
        component: () => import('@/pages/RegistroPage.vue'),
        meta: { publica: true, titulo: 'Criar conta' },
      },
    ],
  },
  {
    path: '/paciente',
    component: AppLayout,
    children: filhas(
      ['paciente'],
      [
        {
          path: '',
          name: 'paciente-inicio',
          component: () => import('@/pages/paciente/InicioPage.vue'),
          meta: { titulo: 'Início' },
        },
        {
          path: 'agendar',
          name: 'paciente-agendar',
          component: () => import('@/pages/AgendarPage.vue'),
          meta: { titulo: 'Agendar consulta' },
        },
        {
          path: 'agendamentos',
          name: 'paciente-agendamentos',
          component: () => import('@/pages/paciente/AgendamentosPage.vue'),
          meta: { titulo: 'Meus agendamentos' },
        },
        {
          path: 'historico',
          name: 'paciente-historico',
          component: () => import('@/pages/paciente/HistoricoPage.vue'),
          meta: { titulo: 'Histórico clínico' },
        },
        {
          path: 'receitas',
          name: 'paciente-receitas',
          component: () => import('@/pages/paciente/ReceitasPage.vue'),
          meta: { titulo: 'Receitas' },
        },
        {
          path: 'resultados',
          name: 'paciente-resultados',
          component: () => import('@/pages/paciente/ResultadosPage.vue'),
          meta: { titulo: 'Resultados de exames' },
        },
        {
          path: 'equipe',
          name: 'paciente-equipe',
          component: () => import('@/pages/paciente/EquipePage.vue'),
          meta: { titulo: 'Minha equipe' },
        },
        {
          path: 'teleconsulta/:sala',
          name: 'paciente-teleconsulta',
          component: () => import('@/pages/TeleconsultaPage.vue'),
          meta: { titulo: 'Teleconsulta' },
        },
        {
          path: 'privacidade',
          name: 'paciente-privacidade',
          component: () => import('@/pages/paciente/PrivacidadePage.vue'),
          meta: { titulo: 'Privacidade' },
        },
      ],
    ),
  },
  {
    path: '/recepcao',
    component: AppLayout,
    children: filhas(
      ['secretario', 'administrador'],
      [
        {
          path: 'pacientes',
          name: 'recepcao-pacientes',
          component: () => import('@/pages/recepcao/PacientesPage.vue'),
          meta: { titulo: 'Pacientes' },
        },
        {
          path: 'agenda',
          name: 'recepcao-agenda',
          component: () => import('@/pages/recepcao/AgendaPage.vue'),
          meta: { titulo: 'Agenda' },
        },
        {
          path: 'agendar',
          name: 'recepcao-agendar',
          component: () => import('@/pages/AgendarPage.vue'),
          meta: { titulo: 'Agendar para paciente' },
        },
      ],
    ),
  },
  {
    path: '/profissional',
    component: AppLayout,
    children: filhas(
      ['profissional_saude'],
      [
        {
          path: 'agenda',
          name: 'profissional-agenda',
          component: () => import('@/pages/profissional/AgendaPage.vue'),
          meta: { titulo: 'Minha agenda' },
        },
        {
          path: 'atendimento/:id',
          name: 'profissional-atendimento',
          component: () => import('@/pages/profissional/AtendimentoPage.vue'),
          meta: { titulo: 'Atendimento' },
        },
        {
          path: 'teleconsulta/:sala',
          name: 'profissional-teleconsulta',
          component: () => import('@/pages/TeleconsultaPage.vue'),
          meta: { titulo: 'Teleconsulta' },
        },
      ],
    ),
  },
  {
    path: '/admin',
    component: AppLayout,
    children: filhas(
      ['administrador'],
      [
        {
          path: 'relatorios',
          name: 'admin-relatorios',
          component: () => import('@/pages/admin/RelatoriosPage.vue'),
          meta: { titulo: 'Relatórios' },
        },
        {
          path: 'usuarios',
          name: 'admin-usuarios',
          component: () => import('@/pages/admin/UsuariosPage.vue'),
          meta: { titulo: 'Usuários' },
        },
        {
          path: 'perfis',
          name: 'admin-perfis',
          component: () => import('@/pages/admin/PerfisPage.vue'),
          meta: { titulo: 'Perfis e permissões' },
        },
        {
          path: 'unidades',
          name: 'admin-unidades',
          component: () => import('@/pages/admin/UnidadesPage.vue'),
          meta: { titulo: 'Unidades' },
        },
        {
          path: 'logs',
          name: 'admin-logs',
          component: () => import('@/pages/admin/LogsPage.vue'),
          meta: { titulo: 'Auditoria' },
        },
      ],
    ),
  },
  {
    path: '/',
    component: PublicLayout,
    meta: { publica: true },
    children: [
      {
        path: '403',
        name: 'proibido',
        component: () => import('@/pages/ErroPage.vue'),
        props: { codigo: 403 },
        meta: { publica: true, titulo: 'Acesso negado' },
      },
      {
        path: ':caminho(.*)*',
        name: 'nao-encontrada',
        component: () => import('@/pages/ErroPage.vue'),
        props: { codigo: 404 },
        meta: { publica: true, titulo: 'Página não encontrada' },
      },
    ],
  },
]

export const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: rotas,
})

/** Guarda: sem sessão → login; perfil errado → 403. (A autorização real é SEMPRE da API.) */
router.beforeEach((para) => {
  const auth = useAuthStore()

  if (para.path === '/') return auth.autenticado ? auth.inicio : { name: 'login' }
  if (para.meta.publica) {
    // quem já está logado não volta para o login/registro
    return auth.autenticado && (para.name === 'login' || para.name === 'registro')
      ? auth.inicio
      : true
  }
  if (!auth.autenticado) return { name: 'login', query: { destino: para.fullPath } }

  const permitidos = para.meta.perfis
  if (permitidos && !(auth.perfil && permitidos.includes(auth.perfil))) return { name: 'proibido' }
  return true
})

/** Acessibilidade em SPA: título da página + foco no conteúdo a cada navegação (leitores de tela anunciam a nova tela). */
router.afterEach((para) => {
  document.title = `${para.meta.titulo ?? 'VidaPlus'} · VidaPlus SGHSS`
  queueMicrotask(() => document.getElementById('conteudo')?.focus({ preventScroll: false }))
})

export default router
