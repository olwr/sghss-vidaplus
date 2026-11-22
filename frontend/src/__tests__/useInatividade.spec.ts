import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'
import { mount } from '@vue/test-utils'
import { useInatividade } from '@/composables/useInatividade'

describe('useInatividade (CT041)', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })
  afterEach(() => {
    vi.useRealTimers()
  })

  function montar(aoExpirar = vi.fn<() => void>()) {
    let api!: ReturnType<typeof useInatividade>
    const w = mount(
      defineComponent({
        setup() {
          api = useInatividade({ limiteMs: 10_000, avisoMs: 2_000, aoExpirar })
          return () => h('div')
        },
      }),
    )
    return { api, w, aoExpirar }
  }

  it('avisa antes e expira no limite', () => {
    const { api, aoExpirar } = montar()
    vi.advanceTimersByTime(8_000)
    expect(api.aviso.value).toBe(true)
    expect(aoExpirar).not.toHaveBeenCalled()
    vi.advanceTimersByTime(2_000)
    expect(aoExpirar).toHaveBeenCalledTimes(1)
  })
  it('atividade reinicia a contagem', () => {
    const { aoExpirar } = montar()
    vi.advanceTimersByTime(7_000)
    globalThis.dispatchEvent(new Event('keydown'))
    vi.advanceTimersByTime(7_000)
    expect(aoExpirar).not.toHaveBeenCalled()
  })
  it('durante o aviso só o botão explícito salva a sessão', () => {
    const { api, aoExpirar } = montar()
    vi.advanceTimersByTime(8_500)
    globalThis.dispatchEvent(new Event('mousemove'))
    expect(api.aviso.value).toBe(true)
    api.reiniciar()
    expect(api.aviso.value).toBe(false)
    vi.advanceTimersByTime(9_000)
    expect(aoExpirar).not.toHaveBeenCalled()
  })
  it('desmontar cancela os timers', () => {
    const { w, aoExpirar } = montar()
    w.unmount()
    vi.advanceTimersByTime(20_000)
    expect(aoExpirar).not.toHaveBeenCalled()
  })
})
