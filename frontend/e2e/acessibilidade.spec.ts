import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'
import { aparencia, entrarComoPaciente, simularApi, type Aparencia } from './apoio'

/** CT039 — WCAG 2.2 AA com contraste medido no navegador, nos 4 temas, nas telas-chave. */
const TEMAS: Aparencia[] = ['claro', 'escuro', 'alto contraste claro', 'alto contraste escuro']
const TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']

for (const tema of TEMAS) {
  test.describe(`tema: ${tema}`, () => {
    test.beforeEach(async ({ page }) => {
      await simularApi(page)
      await aparencia(page, tema)
    })

    test('login e registro', async ({ page }) => {
      for (const rota of ['/login', '/registro']) {
        await page.goto(rota)
        await page.waitForSelector('h1')
        const r = await new AxeBuilder({ page }).withTags(TAGS).analyze()
        expect(
          r.violations.map((v) => `${v.id} (${v.nodes.length}): ${v.nodes[0]?.html.slice(0, 120)}`),
          rota,
        ).toEqual([])
      }
    })

    test('portal do paciente (início e agendar)', async ({ page }) => {
      await entrarComoPaciente(page)
      for (const [nome, ir] of [
        ['Início', null],
        ['Agendar', '/paciente/agendar'],
      ] as const) {
        if (ir)
          await page
            .getByRole('link', { name: /agendar consulta/i })
            .first()
            .click()
        await page.waitForSelector('h1')
        const r = await new AxeBuilder({ page }).withTags(TAGS).analyze()
        expect(
          r.violations.map((v) => `${v.id} (${v.nodes.length}): ${v.nodes[0]?.html.slice(0, 120)}`),
          nome,
        ).toEqual([])
      }
    })
  })
}

test('teclado: o primeiro Tab leva ao "Ir para o conteúdo" e o foco é visível', async ({
  page,
}) => {
  await simularApi(page)
  await page.goto('/login')
  await page.waitForSelector('h1') // espera o app montar antes do primeiro Tab
  await page.keyboard.press('Tab')
  const skip = page.getByRole('link', { name: 'Ir para o conteúdo' })
  await expect(skip).toBeFocused()
  await expect(skip).toBeVisible()
  await page.keyboard.press('Enter')
  await expect(page.locator('#conteudo')).toBeFocused()
})
