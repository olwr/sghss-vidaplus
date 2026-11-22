import { defineConfig, devices } from '@playwright/test'

/**
 * E2E com a API SIMULADA (page.route): prova o front sem depender do back-end.
 * O navegador é real (Chromium), então o axe mede CONTRASTE de verdade (o jsdom do Vitest não mede).
 */
export default defineConfig({
  testDir: './e2e',
  outputDir: './e2e/.resultados',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
  use: {
    baseURL: 'http://localhost:4173',
    locale: 'pt-BR',
    timezoneId: 'America/Sao_Paulo',
    trace: 'retain-on-failure',
  },
  webServer: {
    command: 'npm run build-only && npm run preview -- --port 4173 --strictPort',
    url: 'http://localhost:4173',
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
    // O .env NÃO vai para o Git; sem isto o build do CI fica sem VITE_API_URL e as chamadas não casam com o page.route.
    env: { VITE_API_URL: 'http://localhost:8000/api/v1', VITE_JITSI_DOMAIN: 'localhost:8443' },
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
})
