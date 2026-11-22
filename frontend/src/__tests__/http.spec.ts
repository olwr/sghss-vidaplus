import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { AxiosAdapter, AxiosResponse, InternalAxiosRequestConfig } from 'axios'
import { aoReceber401, definirToken, errosPorCampo, http, mensagemDe } from '@/api/http'

function responder(status: number, data: unknown): AxiosAdapter {
  return (config: InternalAxiosRequestConfig) => {
    const r = { data, status, statusText: '', headers: {}, config } as AxiosResponse
    return status < 400
      ? Promise.resolve(r)
      : Promise.reject(Object.assign(new Error('x'), { response: r, config, isAxiosError: true }))
  }
}

describe('cliente http', () => {
  beforeEach(() => {
    definirToken(null)
    aoReceber401(() => undefined)
  })

  it('envia Bearer quando há token', async () => {
    definirToken('tok')
    let enviado = ''
    await http.get('/x', {
      adapter: (c) => {
        enviado = String(c.headers.Authorization)
        return responder(200, {})(c)
      },
    })
    expect(enviado).toBe('Bearer tok')
  })
  it('converte problem+json em Problema e extrai erros por campo', async () => {
    const corpo = {
      title: 'Dados inválidos',
      detail: 'Corrija os campos.',
      errors: { cpf: ['CPF inválido.', 'x'] },
    }
    const e = await http.get('/x', { adapter: responder(422, corpo) }).catch((x) => x)
    expect(mensagemDe(e)).toBe('Corrija os campos.')
    expect(errosPorCampo(e)).toEqual({ cpf: 'CPF inválido.' })
    expect(e.status).toBe(422)
  })
  it('401 fora do login chama o callback; no login não chama', async () => {
    const cb = vi.fn<() => void>()
    aoReceber401(cb)
    await http
      .get('/pacientes', { adapter: responder(401, { title: 'Não autenticado', detail: 'x' }) })
      .catch(() => undefined)
    expect(cb).toHaveBeenCalledTimes(1)
    await http
      .post('/auth/login', {}, { adapter: responder(401, { title: 'Credenciais', detail: 'x' }) })
      .catch(() => undefined)
    expect(cb).toHaveBeenCalledTimes(1)
  })
  it('falha de rede vira mensagem amigável', async () => {
    const e = await http
      .get('/x', { adapter: () => Promise.reject(new Error('net')) })
      .catch((x) => x)
    expect(mensagemDe(e)).toMatch(/conexão/i)
  })
})

describe('downloads (blob)', () => {
  it('erro 403 em download vira Problema legível (corpo JSON vem como Blob)', async () => {
    const corpo = new Blob([JSON.stringify({ title: 'Proibido', detail: 'Sem permissão.' })], {
      type: 'application/problem+json',
    })
    const e = await http
      .get('/receitas/1/pdf', { responseType: 'blob', adapter: responder(403, corpo) })
      .catch((x) => x)
    expect(mensagemDe(e)).toBe('Sem permissão.')
  })
  it('exigirPdf rejeita conteúdo que não começa com %PDF e mostra o início', async () => {
    const { exigirPdf } = await import('@/lib/arquivo')
    await expect(
      exigirPdf(new Blob(['<br />Deprecated: algo'], { type: 'application/pdf' })),
    ).rejects.toMatchObject({ title: 'Arquivo inválido' })
    await expect(exigirPdf(new Blob(['%PDF-1.7 ...']))).resolves.toBeInstanceOf(Blob)
  })
})

describe('extensaoDe', () => {
  it('usa o tipo real do arquivo', async () => {
    const { extensaoDe } = await import('@/lib/arquivo')
    expect(extensaoDe('application/pdf')).toBe('pdf')
    expect(extensaoDe('image/png')).toBe('png')
    expect(extensaoDe('application/json; charset=utf-8')).toBe('json')
    expect(extensaoDe('x/y')).toBe('bin')
  })
})
