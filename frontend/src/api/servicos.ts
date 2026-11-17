import { exigirPdf } from '@/lib/arquivo'
import { http } from './http'
import type {
  Agendamento,
  Equipe,
  Exame,
  Horario,
  LoginResposta,
  Pagina,
  Paciente,
  Profissional,
  Prontuario,
  Receita,
  RelatorioDados,
  SalaEntrada,
  Unidade,
  UnidadeResumo,
  UsuarioAdmin,
  Usuario,
} from '@/types/api'

type Corpo = Record<string, unknown>
const dados = <T>(r: { data: { data: T } }): T => r.data.data

export const authApi = {
  login: (email: string, password: string) =>
    http.post<LoginResposta>('/auth/login', { email, password }).then((r) => r.data),
  registrar: (corpo: Corpo) => http.post('/auth/registro', corpo).then((r) => r.data),
  unidades: () => http.get<{ data: UnidadeResumo[] }>('/auth/unidades').then(dados),
  sair: () => http.post('/auth/logout'),
  eu: () => http.get<{ data: Usuario }>('/auth/me').then(dados),
}

export const pacientesApi = {
  listar: (params: Corpo) =>
    http.get<Pagina<Paciente>>('/pacientes', { params }).then((r) => r.data),
  criar: (corpo: Corpo) => http.post<{ data: Paciente }>('/pacientes', corpo).then(dados),
  equipe: (id: string) => http.get<{ data: Equipe[] }>(`/pacientes/${id}/equipe`).then(dados),
  prontuario: (id: string) =>
    http.get<{ data: Prontuario }>(`/pacientes/${id}/prontuario`).then(dados),
}

export const titularApi = {
  meusDados: () => http.get<{ data: Paciente }>('/titular/meus-dados').then(dados),
  consentimentos: () =>
    http
      .get<{ data: { finalidade: string; vigente: boolean }[] }>('/titular/consentimentos')
      .then(dados),
  conceder: (finalidade: string) => http.post('/titular/consentimentos', { finalidade }),
  revogar: (finalidade: string) => http.delete(`/titular/consentimentos/${finalidade}`),
  exportar: () =>
    http.get<Blob>('/titular/exportacao', { responseType: 'blob' }).then((r) => r.data),
  receitas: () => http.get<{ data: Receita[] }>('/titular/receitas').then(dados),
  exames: () => http.get<{ data: Exame[] }>('/titular/exames').then(dados),
}

export const profissionaisApi = {
  listar: (params: Corpo = {}) =>
    http.get<Pagina<Profissional>>('/profissionais', { params }).then((r) => r.data),
}

export const unidadesApi = {
  listar: (params: Corpo = {}) =>
    http.get<{ data: Unidade[] }>('/unidades', { params }).then(dados),
}

export const agendamentosApi = {
  disponibilidade: (profissional: string, data: string, unidade?: string) =>
    http
      .get<{ data: Horario[] }>('/agendas/disponibilidade', {
        params: { profissional, data, unidade },
      })
      .then(dados),
  listar: (params: Corpo = {}) =>
    http.get<Pagina<Agendamento>>('/agendamentos', { params }).then((r) => r.data),
  /** `chave` = Idempotency-Key: o MESMO valor em reenvios da mesma tentativa evita agendamento duplicado. */
  criar: (corpo: Corpo, chave: string) =>
    http
      .post<{ data: Agendamento }>('/agendamentos', corpo, {
        headers: { 'Idempotency-Key': chave },
      })
      .then(dados),
  acao: (
    id: string,
    acao: 'confirmar' | 'cancelar' | 'remarcar' | 'iniciar' | 'concluir' | 'faltou',
    corpo: Corpo = {},
  ) => http.post<{ data: Agendamento }>(`/agendamentos/${id}/${acao}`, corpo).then(dados),
}

export const clinicoApi = {
  registrar: (prontuarioId: string, corpo: Corpo) =>
    http.post(`/prontuarios/${prontuarioId}/registros`, corpo),
  criarProntuario: (corpo: Corpo) =>
    http.post<{ data: Prontuario }>('/prontuarios', corpo).then(dados),
  emitirReceita: (corpo: Corpo) => http.post<{ data: Receita }>('/receitas', corpo).then(dados),
  pdfReceita: (id: string) =>
    http.get<Blob>(`/receitas/${id}/pdf`, { responseType: 'blob' }).then((r) => exigirPdf(r.data)),
  /** A API devolve o ARQUIVO (PDF/imagem) ou, se o resultado não tem arquivo, um JSON `{ resumo }`. */
  resultadoExame: (id: string) =>
    http.get<Blob>(`/exames/${id}/resultado`, { responseType: 'blob' }).then((r) => r.data),
}

export const telemedicinaApi = {
  evento: (sala: string, evento: string, detalhes?: Corpo) =>
    http.post(`/telemedicina/salas/${sala}/eventos`, { evento, detalhes }),
  entrar: (sala: string) =>
    http.post<SalaEntrada>(`/telemedicina/salas/${sala}/entrar`).then((r) => r.data),
  encerrar: (sala: string) => http.post(`/telemedicina/salas/${sala}/encerrar`),
}

export const adminApi = {
  usuarios: (params: Corpo = {}) =>
    http.get<Pagina<UsuarioAdmin>>('/admin/usuarios', { params }).then((r) => r.data),
  atualizarUsuario: (id: string, corpo: Corpo) => http.patch(`/admin/usuarios/${id}`, corpo),
  perfis: () =>
    http.get<{ data: { perfil: string; permissoes: string[] }[] }>('/admin/perfis').then(dados),
  atividades: (params: Corpo = {}) =>
    http.get('/auditoria/atividades', { params }).then((r) => r.data),
  acessos: (params: Corpo = {}) => http.get('/auditoria/acessos', { params }).then((r) => r.data),
  relatorio: (tipo: string, params: Corpo = {}) =>
    http.get<RelatorioDados>(`/relatorios/${tipo}`, { params }).then((r) => r.data),
}
