import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import type { Usuario } from '@/types/api'

vi.mock('@/api/servicos', () => ({
  authApi: {
    login: vi.fn<() => Promise<unknown>>(),
    sair: vi.fn<() => Promise<void>>().mockRejectedValue(new Error('401')),
  },
}))
import { authApi } from '@/api/servicos'

const usuario = (perfil: Usuario['perfis'][number], permissoes: string[] = []): Usuario => ({
  id: '1',
  nome: 'T',
  email: 't@t.com',
  telefone: null,
  ativo: true,
  unidade_id: null,
  perfis: [perfil],
  permissoes,
  ultimo_login_em: null,
})

describe('store de autenticação', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('entrar guarda a sessão só em memória (nada em localStorage)', async () => {
    vi.mocked(authApi.login).mockResolvedValue({
      token: 'abc',
      tipo: 'Bearer',
      expira_em: '',
      usuario: usuario('paciente'),
    })
    const auth = useAuthStore()
    await auth.entrar('t@t.com', 'x')
    expect(auth.autenticado).toBe(true)
    expect(auth.inicio).toBe('/paciente')
    expect(localStorage.length).toBe(0)
    expect(JSON.stringify(sessionStorage)).not.toContain('abc')
  })
  it('rota inicial por perfil e pode()', () => {
    const auth = useAuthStore()
    auth.iniciar('t', usuario('secretario', ['pacientes.ver']))
    expect(auth.inicio).toBe('/recepcao/pacientes')
    expect(auth.pode('pacientes.ver')).toBe(true)
    expect(auth.pode('usuarios.gerir')).toBe(false)
  })
  it('sair limpa a sessão mesmo se a API falhar', async () => {
    const auth = useAuthStore()
    auth.iniciar('t', usuario('paciente'))
    await auth.sair()
    expect(auth.autenticado).toBe(false)
  })
})
