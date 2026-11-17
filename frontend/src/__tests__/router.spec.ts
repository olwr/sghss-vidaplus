import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { router } from '@/router'
import { useAuthStore } from '@/stores/auth'
import type { Perfil } from '@/types/api'

const logar = (perfil: Perfil) =>
  useAuthStore().iniciar('t', {
    id: '1',
    nome: 'T',
    email: 't@t',
    telefone: null,
    ativo: true,
    unidade_id: null,
    perfis: [perfil],
    permissoes: [],
    ultimo_login_em: null,
  })

describe('guarda de rotas', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    await router.replace('/login')
  })

  it('sem sessão → login com destino', async () => {
    await router.push('/paciente/receitas')
    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.destino).toBe('/paciente/receitas')
  })
  it('perfil errado → 403', async () => {
    logar('paciente')
    await router.push('/admin/usuarios')
    expect(router.currentRoute.value.name).toBe('proibido')
  })
  it('perfil certo acessa', async () => {
    logar('administrador')
    await router.push('/admin/usuarios')
    expect(router.currentRoute.value.name).toBe('admin-usuarios')
  })
  it('logado não volta ao login', async () => {
    logar('paciente')
    await router.push('/registro')
    expect(router.currentRoute.value.path).toBe('/paciente')
  })
})
