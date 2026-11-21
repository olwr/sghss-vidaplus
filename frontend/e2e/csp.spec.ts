import { createServer, type Server } from 'node:http'
import { readFileSync, existsSync, statSync } from 'node:fs'
import { extname, join, normalize } from 'node:path'
import { expect, test } from '@playwright/test'
import { simularApi } from './apoio'

/**
 * Serve o dist/ com EXATAMENTE os cabeçalhos de docker/httpd-spa.conf (marcadores trocados como no
 * build da imagem) e exige zero violações de CSP nas telas principais.
 * Assim a política que vai para produção é a que foi testada no navegador.
 */
const PORTA = 4180
const RAIZ = join(import.meta.dirname, '..', 'dist')
const TIPOS: Record<string, string> = {
  '.html': 'text/html',
  '.js': 'text/javascript',
  '.css': 'text/css',
  '.ico': 'image/x-icon',
  '.svg': 'image/svg+xml',
  '.woff2': 'font/woff2',
}

function cabecalhosDoApache(): Record<string, string> {
  const conf = readFileSync(join(import.meta.dirname, '..', 'docker', 'httpd-spa.conf'), 'utf8')
    .replaceAll('__API_ORIGEM__', 'http://localhost:8000')
    .replaceAll('__JITSI_ORIGEM__', 'https://localhost:8443')
  const cab: Record<string, string> = {}
  for (const m of conf.matchAll(/^\s*Header always set (\S+) "(.*)"\s*$/gm)) cab[m[1]!] = m[2]!
  return cab
}

let servidor: Server
const cab = cabecalhosDoApache()

test.beforeAll(async () => {
  servidor = createServer((req, res) => {
    const alvo = normalize(
      join(RAIZ, decodeURIComponent(new URL(req.url ?? '/', 'http://x').pathname)),
    )
    const arquivo =
      alvo.startsWith(RAIZ) && existsSync(alvo) && statSync(alvo).isFile()
        ? alvo
        : join(RAIZ, 'index.html')
    res.writeHead(200, {
      'content-type': TIPOS[extname(arquivo)] ?? 'application/octet-stream',
      ...cab,
    })
    res.end(readFileSync(arquivo))
  })
  await new Promise<void>((ok) => servidor.listen(PORTA, ok))
})
test.afterAll(() => servidor.close())

async function observarViolacoes(page: import('@playwright/test').Page): Promise<string[]> {
  const violacoes: string[] = []
  page.on('console', (m) => {
    if (/Content Security Policy/i.test(m.text())) violacoes.push(m.text())
  })
  await page.addInitScript(() => {
    document.addEventListener('securitypolicyviolation', (e) => {
      ;(window as unknown as { __csp: string[] }).__csp ??= []
      ;(window as unknown as { __csp: string[] }).__csp.push(
        `${e.violatedDirective} → ${e.blockedURI}`,
      )
    })
  })
  return violacoes
}

test('os cabeçalhos de segurança chegam ao navegador', async ({ request }) => {
  const r = await request.get(`http://localhost:${PORTA}/login`)
  const h = r.headers()
  expect(h['content-security-policy']).toContain("script-src 'self'")
  expect(h['content-security-policy']).not.toMatch(/unsafe-(inline|eval)/)
  expect(h['content-security-policy']).toContain('frame-src https://localhost:8443')
  expect(h['x-content-type-options']).toBe('nosniff')
  expect(h['x-frame-options']).toBe('DENY')
})

test('login, portal e agendamento rodam sem nenhuma violação de CSP', async ({ page }) => {
  const violacoes = await observarViolacoes(page)
  await simularApi(page)
  await page.goto(`http://localhost:${PORTA}/login`)
  await expect(page.getByRole('heading', { name: /entrar/i })).toBeVisible()
  await page.getByLabel('E-mail').fill('maria@vidaplus.test')
  await page.getByLabel('Senha').fill('Senha@12345')
  await page.getByRole('button', { name: 'Entrar' }).click()
  await page.waitForURL('**/paciente')
  // navegação DENTRO do app (um page.goto recarregaria a página e perderia o token, que só vive em memória)
  await page
    .getByRole('link', { name: /agendar/i })
    .first()
    .click()
  await page.waitForURL('**/paciente/agendar')
  await expect(page.getByRole('heading', { name: 'Agendar consulta' })).toBeVisible()

  const internas = await page.evaluate(
    () => (window as unknown as { __csp?: string[] }).__csp ?? [],
  )
  expect([...violacoes, ...internas]).toEqual([])
})

test('prova de sensibilidade: a CSP realmente bloqueia script inline injetado', async ({
  page,
}) => {
  await simularApi(page)
  await page.goto(`http://localhost:${PORTA}/login`)
  const executou = await page.evaluate(() => {
    const s = document.createElement('script')
    s.textContent = 'window.__xss = true'
    document.head.appendChild(s)
    return (window as unknown as { __xss?: boolean }).__xss === true
  })
  expect(executou).toBe(false)
})
