import { beforeEach, describe, expect, it } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import App from '@/App.vue'
import { router } from '@/router'

/** Pega o erro "página em branco": o layout precisa de <RouterView/> para mostrar a tela filha. */
describe('App + layouts', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('/login mostra o formulário dentro do layout público', async () => {
    await router.push('/login')
    await router.isReady()
    const w = mount(App, { global: { plugins: [router, createPinia()] } })
    await flushPromises()
    expect(w.find('main#conteudo').exists()).toBe(true)
    expect(w.find('h1').text()).toBe('Entrar')
    expect(w.find('input[type="email"]').exists()).toBe(true)
  })
})
