/** Contratos da API v1 (resumidos ao que o front consome). A fonte da verdade é docs/openapi.json. */

export interface Problema {
  type?: string
  title: string
  status?: number
  detail: string
  instance?: string
  errors?: Record<string, string[]>
}

export interface Pagina<T> {
  data: T[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export type Perfil = 'administrador' | 'secretario' | 'profissional_saude' | 'paciente'

export interface Usuario {
  id: string
  nome: string
  email: string
  telefone: string | null
  ativo: boolean
  unidade_id: string | null
  perfis: Perfil[]
  permissoes: string[]
  ultimo_login_em: string | null
}

export interface LoginResposta {
  token: string
  tipo: 'Bearer'
  expira_em: string
  usuario: Usuario
}

export interface UnidadeResumo {
  id: string
  nome: string
  tipo: string
  status?: string
}

export interface Unidade extends UnidadeResumo {
  cnpj: string
  telefone: string | null
  email: string | null
}

export interface Endereco {
  cep: string | null
  logradouro: string | null
  numero: string | null
  complemento: string | null
  bairro: string | null
  cidade: string | null
  uf: string | null
}

export interface Paciente {
  id: string
  nome: string
  cpf: string | null
  data_nascimento: string
  sexo: string
  email: string | null
  telefone: string | null
  endereco: Endereco
  unidade_id?: string
}

export interface Profissional {
  id: string
  nome?: string
  tipo: string
  especialidade: string | null
  conselho: string
  ativo: boolean
  unidade_id?: string
}

export type StatusAgendamento =
  'agendado' | 'confirmado' | 'em_atendimento' | 'concluido' | 'cancelado' | 'remarcado' | 'faltou'

export interface Agendamento {
  id: string
  tipo: string
  modalidade: 'presencial' | 'telemedicina'
  status: StatusAgendamento
  inicio_em: string
  fim_em: string
  motivo: string | null
  paciente_id?: string
  paciente_nome?: string
  profissional_id?: string
  profissional_nome?: string
  unidade_id?: string
  unidade_nome?: string
  sala_telemedicina_id?: string | null
  motivo_cancelamento?: string | null
}

export interface Horario {
  inicio: string
  fim: string
  unidade_id: string
}

export interface RegistroClinico {
  id: string
  registrado_em: string
  queixa_principal: string | null
  diagnostico: string | null
  conduta: string | null
  observacoes: string | null
}

export interface Prontuario {
  id: string
  tipo_sanguineo: string | null
  alergias: string | null
  observacoes_gerais: string | null
  registros: RegistroClinico[]
}

export interface ItemReceita {
  medicamento: string
  dosagem: string | null
  posologia: string | null
  duracao: string | null
  quantidade: number | null
}

export interface Receita {
  id: string
  status: string
  emitida_em: string
  itens: ItemReceita[]
  pdf: string
}

export interface Exame {
  id: string
  nome: string
  instrucoes: string | null
  status: string
  solicitado_em: string | null
  realizado_em: string | null
  resultado_disponivel?: boolean
}

export interface Equipe {
  id: string
  nome: string
  descricao: string | null
  ativa: boolean
  profissionais?: { id: string; nome: string | null; tipo: string; funcao: string | null }[]
}

export interface SalaEntrada {
  sala: string
  dominio: string
  jwt: string
  expira_em: string
}

export interface UsuarioAdmin {
  id: string
  nome: string
  email: string
  ativo: boolean
  perfis: Perfil[]
  unidade_id: string | null
}

export interface RelatorioDados {
  titulo: string
  colunas: string[]
  linhas: (string | number | null)[][]
}
