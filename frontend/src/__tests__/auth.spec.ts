import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
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
    // Node ≥ 22 tem um localStorage experimental que SOBREPÕE o do jsdom (e vem indefinido sem --localstorage-file, como no CI).
    // Storage em memória, igual nos dois ambientes:
    const memoria = new Map<string, string>()
    const armazenamento = {
      get length() {
        return memoria.size
      },
      getItem: (k: string) => memoria.get(k) ?? null,
      setItem: (k: string, v: string) => void memoria.set(k, String(v)),
      removeItem: (k: string) => void memoria.delete(k),
      clear: () => memoria.clear(),
      key: (i: number) => [...memoria.keys()][i] ?? null,
    }
    vi.stubGlobal('localStorage', armazenamento)
    vi.stubGlobal('sessionStorage', { ...armazenamento, length: 0 })
  })
  afterEach(() => {
    vi.unstubAllGlobals()
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
    expect(sessionStorage.getItem('token')).toBeNull()
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
