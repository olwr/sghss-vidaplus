import { expect, test } from '@playwright/test'
import { entrarComoPaciente, simularApi } from './apoio'

/** CT041 — logout automático: aviso 1 min antes e sessão encerrada aos 15 min (relógio simulado, sem esperar). */
test('inatividade: avisa aos 14 min e encerra aos 15', async ({ page }) => {
  await simularApi(page)
  await page.clock.install()
  await entrarComoPaciente(page)

  await page.clock.fastForward('14:05')
  await expect(page.getByText('Sua sessão vai expirar por inatividade em 1 minuto.')).toBeVisible()
  await expect(page.getByRole('button', { name: 'Continuar conectado' })).toBeVisible()

  await page.clock.fastForward('01:00')
  await expect(page).toHaveURL(/\/login\?inativo=1/)
  await expect(page.getByText(/encerrada por inatividade/i)).toBeVisible()
})

test('sessão só em memória: recarregar a página volta ao login', async ({ page }) => {
  await simularApi(page)
  await entrarComoPaciente(page)
  await page.reload()
  await expect(page).toHaveURL(/\/login/)
  expect(
    await page.evaluate(() => JSON.stringify({ ...localStorage, ...sessionStorage })),
  ).not.toContain('token-de-teste')
})
