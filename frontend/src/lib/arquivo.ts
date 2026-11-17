/** Baixa um Blob recebido da API (PDF de receita, resultado de exame, exportação LGPD). */
export function baixar(blob: Blob, nome: string): void {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = nome
  a.click()
  URL.revokeObjectURL(url)
}

/**
 * Garante que o Blob é mesmo um PDF (começa com "%PDF"). Se a API devolveu outra coisa com status 200
 * (aviso do PHP antes do arquivo, HTML, JSON), mostra o começo do conteúdo em vez de salvar um .pdf "corrompido".
 */
export async function exigirPdf(blob: Blob): Promise<Blob> {
  const inicio = await blob.slice(0, 5).text()
  if (inicio === '%PDF-') return blob
  const trecho = (await blob.slice(0, 200).text()).replace(/\s+/g, ' ').trim()
  throw {
    title: 'Arquivo inválido',
    detail: `O servidor não devolveu um PDF válido. Início da resposta: “${trecho}”`,
  }
}

const EXTENSOES: Record<string, string> = {
  'application/pdf': 'pdf',
  'image/png': 'png',
  'image/jpeg': 'jpg',
  'application/json': 'json',
  'text/plain': 'txt',
}

/** Extensão a partir do tipo real do arquivo (o nome original só chega ao front se a API expuser Content-Disposition no CORS). */
export const extensaoDe = (tipo: string): string => EXTENSOES[tipo.split(';')[0]!.trim()] ?? 'bin'
