import { expect, test } from '@playwright/test'
import { entrarComoPaciente, simularApi } from './apoio'

/** Fluxo principal do paciente + CT023 (clique duplo não duplica; mesma Idempotency-Key). */
test('paciente agenda uma consulta em 5 passos e o clique duplo gera UMA requisição lógica', async ({
  page,
}) => {
  const chamadas = await simularApi(page)
  await entrarComoPaciente(page)
  await page
    .getByRole('link', { name: /agendar consulta/i })
    .first()
    .click()

  await page.getByLabel('1. Unidade').selectOption({ label: 'Clínica Central' })
  await page.getByLabel('2. Profissional').selectOption({ index: 1 })
  await page.getByRole('button', { name: 'Ver horários livres' }).click()
  await expect(page.getByRole('status').filter({ hasText: 'horário(s) livre(s)' })).toBeVisible()
  await page.locator('label:has(input[name="horario"])').first().click()

  const confirmar = page.getByRole('button', { name: 'Confirmar agendamento' })
  await confirmar.dblclick()

  await expect(page).toHaveURL(/\/paciente\/agendamentos/)
  const chaves = new Set(chamadas.agendamentosPost.map((c) => c.chave))
  expect(chamadas.agendamentosPost.length).toBeGreaterThan(0)
  expect(chaves.size, 'todas as tentativas usam a MESMA Idempotency-Key').toBe(1)
  expect([...chaves][0]).toMatch(/^[0-9a-f-]{36}$/)
})

test('credenciais erradas mostram o erro da API em português', async ({ page }) => {
  await simularApi(page)
  await page.goto('/login')
  await page.getByLabel('E-mail').fill('maria@vidaplus.test')
  await page.getByLabel('Senha').fill('errada-errada')
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page.getByRole('alert')).toContainText('E-mail ou senha incorretos.')
})
