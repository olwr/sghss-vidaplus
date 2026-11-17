import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authApi } from '@/api/servicos'
import { definirToken } from '@/api/http'
import type { Perfil, Usuario } from '@/types/api'

/**
 * Sessão SOMENTE em memória (sem localStorage): reduz o dano de um XSS e atende ao "bloqueio por inatividade".
 * Consequência assumida: recarregar a página (F5) exige novo login.
 */
export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const usuario = ref<Usuario | null>(null)

  const autenticado = computed(() => token.value !== null && usuario.value !== null)
  const perfil = computed<Perfil | null>(() => usuario.value?.perfis[0] ?? null)

  const pode = (permissao: string): boolean =>
    usuario.value?.permissoes.includes(permissao) ?? false

  /** Rota inicial de cada perfil. */
  const inicio = computed(() => {
    switch (perfil.value) {
      case 'paciente':
        return '/paciente'
      case 'secretario':
        return '/recepcao/pacientes'
      case 'profissional_saude':
        return '/profissional/agenda'
      case 'administrador':
        return '/admin/relatorios'
      default:
        return '/login'
    }
  })

  function iniciar(novoToken: string, u: Usuario): void {
    token.value = novoToken
    usuario.value = u
    definirToken(novoToken)
  }

  async function entrar(email: string, senha: string): Promise<void> {
    const r = await authApi.login(email, senha)
    iniciar(r.token, r.usuario)
  }

  function limpar(): void {
    token.value = null
    usuario.value = null
    definirToken(null)
  }

  /** Logout best-effort: mesmo que a API falhe, a sessão local é encerrada. */
  async function sair(): Promise<void> {
    try {
      if (token.value) await authApi.sair()
    } catch {
      /* token já inválido no servidor: segue limpando */
    } finally {
      limpar()
    }
  }

  return { token, usuario, autenticado, perfil, inicio, pode, iniciar, entrar, sair, limpar }
})
