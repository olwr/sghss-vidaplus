import { expect, test } from '@playwright/test'
import { aparencia, entrarComoPaciente, simularApi } from './apoio'

/** CT040 — sem rolagem horizontal em 360 px e em 1280 px, também com fonte a 175 %. Gera prints para o PDF. */
const TELAS = [
  { nome: 'mobile-360', largura: 360, altura: 740 },
  { nome: 'desktop-1280', largura: 1280, altura: 800 },
]

for (const t of TELAS) {
  for (const escala of [100, 175] as const) {
    test(`${t.nome} com fonte ${escala}%`, async ({ page }) => {
      await simularApi(page)
      await aparencia(page, 'claro', escala)
      await page.setViewportSize({ width: t.largura, height: t.altura })

      await page.goto('/login')
      await page.waitForSelector('h1')
      await page.screenshot({
        path: `../docs/evidencias/ct040-login-${t.nome}-${escala}.png`,
        fullPage: true,
      })

      await entrarComoPaciente(page)
      await page
        .getByRole('link', { name: /agendar consulta/i })
        .first()
        .click()
      await page.waitForSelector('h1')
      await page.screenshot({
        path: `../docs/evidencias/ct040-agendar-${t.nome}-${escala}.png`,
        fullPage: true,
      })

      const { rolagem, largura } = await page.evaluate(() => ({
        rolagem: document.documentElement.scrollWidth,
        largura: document.documentElement.clientWidth,
      }))
      expect(rolagem, 'rolagem horizontal').toBeLessThanOrEqual(largura)
    })
  }
}
