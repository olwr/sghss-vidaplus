import axios, { type AxiosError } from 'axios'
import type { Problema } from '@/types/api'

/** Token e callback de 401 ficam FORA do módulo da store para evitar import circular (store ↔ http ↔ router). */
let tokenAtual: string | null = null
let aoNaoAutenticar: (() => void) | null = null

export const definirToken = (token: string | null): void => {
  tokenAtual = token
}
export const aoReceber401 = (fn: () => void): void => {
  aoNaoAutenticar = fn
}

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { Accept: 'application/json' },
  timeout: 20_000,
})

http.interceptors.request.use((config) => {
  if (tokenAtual) config.headers.Authorization = `Bearer ${tokenAtual}`
  return config
})

/** application/problem+json (RFC 7807) → sempre rejeita com um `Problema` tipado. */
export const normalizar = (erro: unknown): Problema => {
  const e = erro as AxiosError<Problema>
  if (e.response?.data && typeof e.response.data === 'object' && 'title' in e.response.data) {
    return { ...e.response.data, status: e.response.status }
  }
  if (e.response) {
    return {
      title: 'Erro na requisição',
      detail: 'Não foi possível concluir a operação.',
      status: e.response.status,
    }
  }
  return { title: 'Falha de rede', detail: 'Verifique a conexão e tente novamente.' }
}

http.interceptors.response.use(
  (resposta) => resposta,
  async (erro) => {
    // downloads (responseType 'blob'): o erro RFC 7807 chega como Blob; converte para objeto antes de normalizar
    const corpo = erro?.response?.data
    if (corpo instanceof Blob && corpo.type.includes('json')) {
      try {
        erro.response.data = JSON.parse(await corpo.text())
      } catch {
        /* mantém o blob */
      }
    }
    const problema = normalizar(erro)
    // 401 em rota protegida = sessão expirada/revogada. O login em si também devolve 401 (credenciais): não redireciona.
    if (problema.status === 401 && !String(erro.config?.url ?? '').endsWith('/auth/login')) {
      aoNaoAutenticar?.()
    }
    return Promise.reject(problema)
  },
)

export const mensagemDe = (erro: unknown): string => {
  const p = erro as Partial<Problema>
  return p?.detail || p?.title || 'Ocorreu um erro inesperado.'
}

/** Erros de validação do Laravel → { campo: 'primeira mensagem' } para o VeeValidate (`setErrors`). */
export const errosPorCampo = (erro: unknown): Record<string, string> => {
  const e = (erro as Partial<Problema>)?.errors ?? {}
  return Object.fromEntries(Object.entries(e).map(([campo, msgs]) => [campo, msgs[0] ?? '']))
}
