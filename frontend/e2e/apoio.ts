import type { Page, Route } from '@playwright/test'

/** API simulada: devolve o MESMO formato da API v1 (RFC 7807 nos erros). Nada sai do computador. */
export const usuarioPaciente = {
  id: 'u-1',
  nome: 'Maria Teste',
  email: 'maria@vidaplus.test',
  telefone: null,
  ativo: true,
  unidade_id: null,
  perfis: ['paciente'],
  permissoes: ['agendamentos.criar', 'agendamentos.ver', 'profissionais.ver'],
  ultimo_login_em: null,
}

export interface Chamadas {
  agendamentosPost: { chave: string | null; corpo: unknown }[]
}

const json = (route: Route, corpo: unknown, status = 200) =>
  route.fulfill({
    status,
    contentType: status >= 400 ? 'application/problem+json' : 'application/json',
    body: JSON.stringify(corpo),
  })

export async function simularApi(page: Page): Promise<Chamadas> {
  const chamadas: Chamadas = { agendamentosPost: [] }
  const amanha = new Date(Date.now() + 864e5).toISOString().slice(0, 10)

  await page.route('**/api/v1/**', async (route) => {
    const req = route.request()
    const url = new URL(req.url())
    const caminho = url.pathname.replace('/api/v1', '')
    const metodo = req.method()

    if (metodo === 'OPTIONS')
      return route.fulfill({
        status: 204,
        headers: {
          'access-control-allow-origin': '*',
          'access-control-allow-headers': '*',
          'access-control-allow-methods': '*',
        },
      })
    const cors = { 'access-control-allow-origin': '*' }
    void cors

    if (caminho === '/auth/login') {
      const { password } = req.postDataJSON() as { password: string }
      return password === 'Senha@12345'
        ? json(route, {
            token: 'token-de-teste',
            tipo: 'Bearer',
            expira_em: '2099-01-01T00:00:00Z',
            usuario: usuarioPaciente,
          })
        : json(
            route,
            { title: 'Credenciais inválidas', status: 401, detail: 'E-mail ou senha incorretos.' },
            401,
          )
    }
    if (caminho === '/auth/unidades')
      return json(route, { data: [{ id: 'un-1', nome: 'Clínica Central', tipo: 'clinica' }] })
    if (caminho === '/profissionais') {
      return json(route, {
        data: [
          { id: 'pr-1', nome: 'Dra. Ana Souza', tipo: 'medico', especialidade: 'Clínica geral' },
        ],
        meta: { current_page: 1, last_page: 1, per_page: 100, total: 1 },
      })
    }
    if (caminho === '/agendas/disponibilidade') {
      return json(route, {
        data: [8, 9, 10].map((h) => ({
          inicio: `${amanha}T${String(h + 3).padStart(2, '0')}:00:00Z`,
          fim: `${amanha}T${String(h + 3).padStart(2, '0')}:30:00Z`,
          unidade_id: 'un-1',
        })),
      })
    }
    if (caminho === '/agendamentos' && metodo === 'POST') {
      chamadas.agendamentosPost.push({
        chave: req.headers()['idempotency-key'] ?? null,
        corpo: req.postDataJSON(),
      })
      await new Promise((r) => setTimeout(r, 300)) // rede lenta: dá tempo do clique duplo
      return json(route, { data: { id: 'ag-1', status: 'agendado' } }, 201)
    }
    if (caminho === '/agendamentos')
      return json(route, {
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 50, total: 0 },
      })
    return json(
      route,
      { title: 'Não simulado', status: 404, detail: `Sem simulação para ${metodo} ${caminho}` },
      404,
    )
  })
  return chamadas
}

/** Entra pela tela de login (o token só vive em memória: não dá para "pular" o login com page.goto). */
export async function entrarComoPaciente(page: Page): Promise<void> {
  await page.goto('/login')
  await page.getByLabel('E-mail').fill('maria@vidaplus.test')
  await page.getByLabel('Senha').fill('Senha@12345')
  await page.getByRole('button', { name: 'Entrar' }).click()
  await page.waitForURL('**/paciente')
}

export type Aparencia = 'claro' | 'escuro' | 'alto contraste claro' | 'alto contraste escuro'

/** Aplica a preferência ANTES do app carregar (é o que o painel de acessibilidade grava). */
export async function aparencia(page: Page, a: Aparencia, escala: 100 | 175 = 100): Promise<void> {
  const prefs = {
    escala,
    altoContraste: a.startsWith('alto'),
    tema: a.endsWith('escuro') ? 'dark' : 'light',
  }
  await page.addInitScript(
    (p) => localStorage.setItem('sghss:acessibilidade', JSON.stringify(p)),
    prefs,
  )
}
