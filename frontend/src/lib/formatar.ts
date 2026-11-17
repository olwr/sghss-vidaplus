const FUSO = 'America/Sao_Paulo'

export const dataHora = (iso: string): string =>
  new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
    timeZone: FUSO,
  }).format(new Date(iso))

export const dataCurta = (iso: string): string =>
  new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeZone: FUSO }).format(new Date(iso))

export const hora = (iso: string): string =>
  new Intl.DateTimeFormat('pt-BR', { timeStyle: 'short', timeZone: FUSO }).format(new Date(iso))

/** YYYY-MM-DD de hoje no fuso da clínica (não em UTC). */
export const hojeIso = (): string =>
  new Intl.DateTimeFormat('sv-SE', { timeZone: FUSO }).format(new Date())

export const rotulo = (valor: string): string =>
  valor.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase())

/** Intervalo [de, ate] de um dia (AAAA-MM-DD) no fuso de Brasília (sem horário de verão desde 2019), p/ os filtros `de`/`ate` da API. */
export const intervaloDoDia = (dia: string): { de: string; ate: string } => ({
  de: `${dia}T00:00:00-03:00`,
  ate: `${dia}T23:59:59-03:00`,
})
