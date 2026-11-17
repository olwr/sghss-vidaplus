import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import axe from 'axe-core'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import PaginacaoNav from '@/components/ui/PaginacaoNav.vue'

/** axe no jsdom NÃO mede contraste (sem layout): o contraste foi calculado nos tokens. Aqui: papéis, nomes, rótulos. */
async function violacoes(html: string): Promise<string[]> {
  document.body.innerHTML = `<main>${html}</main>`
  const r = await axe.run(document.body, {
    rules: { 'color-contrast': { enabled: false }, region: { enabled: false } },
  })
  return r.violations.map((v) => `${v.id}: ${v.help}`)
}

describe('StatusBadge', () => {
  it.each(['agendado', 'confirmado', 'cancelado', 'faltou', 'desconhecido'])(
    '%s tem ícone decorativo + texto',
    (s) => {
      const w = mount(StatusBadge, { props: { status: s } })
      expect(w.find('[aria-hidden="true"]').exists()).toBe(true)
      expect(w.text().length).toBeGreaterThan(2)
    },
  )
})

describe('AlertaMsg', () => {
  it('erro usa role=alert; sucesso usa role=status', () => {
    expect(mount(AlertaMsg, { props: { tom: 'erro' } }).attributes('role')).toBe('alert')
    expect(mount(AlertaMsg, { props: { tom: 'sucesso' } }).attributes('role')).toBe('status')
  })
})

describe('axe-core em componentes-chave', () => {
  it('sem violações', async () => {
    const html = [
      mount(BaseButton, { slots: { default: 'Salvar' } }).html(),
      mount(AlertaMsg, { props: { tom: 'erro' }, slots: { default: 'Falhou' } }).html(),
      mount(StatusBadge, { props: { status: 'confirmado' } }).html(),
      mount(PaginacaoNav, { props: { pagina: 1, ultima: 3, total: 40 } }).html(),
    ].join('')
    expect(await violacoes(html)).toEqual([])
  })
})
