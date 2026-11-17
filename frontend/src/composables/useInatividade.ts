import { onBeforeUnmount, onMounted, ref } from 'vue'

const EVENTOS = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'] as const

export interface OpcoesInatividade {
  limiteMs?: number // padrão 15 min
  avisoMs?: number // quanto ANTES do limite avisar (padrão 1 min)
  aoExpirar: () => void
}

/**
 * Bloqueio por inatividade (CT041): avisa 1 min antes (região aria-live) e encerra a sessão no limite.
 * Qualquer interação reinicia; durante o aviso o usuário pode "continuar conectado".
 */
export function useInatividade({
  limiteMs = 15 * 60_000,
  avisoMs = 60_000,
  aoExpirar,
}: OpcoesInatividade) {
  const aviso = ref(false)
  let tAviso: ReturnType<typeof setTimeout> | undefined
  let tFim: ReturnType<typeof setTimeout> | undefined

  function parar(): void {
    clearTimeout(tAviso)
    clearTimeout(tFim)
  }

  function reiniciar(): void {
    parar()
    aviso.value = false
    tAviso = setTimeout(() => {
      aviso.value = true
    }, limiteMs - avisoMs)
    tFim = setTimeout(() => {
      aviso.value = false
      aoExpirar()
    }, limiteMs)
  }

  // durante o aviso, movimento do mouse não "salva" a sessão: só o botão explícito (reiniciar)
  const ouvinte = () => {
    if (!aviso.value) reiniciar()
  }

  onMounted(() => {
    EVENTOS.forEach((e) => globalThis.addEventListener(e, ouvinte, { passive: true }))
    reiniciar()
  })
  onBeforeUnmount(() => {
    EVENTOS.forEach((e) => globalThis.removeEventListener(e, ouvinte))
    parar()
  })

  return { aviso, reiniciar }
}
