import { ref } from 'vue'
import { defineStore } from 'pinia'

export const ESCALAS = [100, 125, 150, 175] as const
export type Escala = (typeof ESCALAS)[number]
export type Tema = 'light' | 'dark'

const CHAVE = 'sghss:acessibilidade'

interface Preferencias {
  escala: Escala
  altoContraste: boolean
  tema: Tema
}

/** Preferências de interface (NÃO sensíveis) → localStorage com try/catch (pode estar bloqueado). */
function ler(): Preferencias {
  const padrao: Preferencias = {
    escala: 100,
    altoContraste: false,
    tema: globalThis.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
  }
  try {
    const bruto = globalThis.localStorage?.getItem(CHAVE)
    if (!bruto) return padrao
    const p = JSON.parse(bruto) as Partial<Preferencias>
    return {
      escala: ESCALAS.includes(p.escala as Escala) ? (p.escala as Escala) : padrao.escala,
      altoContraste: p.altoContraste === true,
      tema: p.tema === 'dark' ? 'dark' : p.tema === 'light' ? 'light' : padrao.tema,
    }
  } catch {
    return padrao
  }
}

export const useAcessibilidadeStore = defineStore('acessibilidade', () => {
  const inicial = ler()
  const escala = ref<Escala>(inicial.escala)
  const altoContraste = ref(inicial.altoContraste)
  const tema = ref<Tema>(inicial.tema)

  function aplicar(): void {
    const raiz = document.documentElement
    raiz.style.fontSize = `${escala.value}%`
    raiz.dataset.theme = tema.value
    if (altoContraste.value) raiz.dataset.contrast = 'high'
    else delete raiz.dataset.contrast
    try {
      globalThis.localStorage?.setItem(
        CHAVE,
        JSON.stringify({
          escala: escala.value,
          altoContraste: altoContraste.value,
          tema: tema.value,
        }),
      )
    } catch {
      /* armazenamento indisponível: a preferência vale só nesta sessão */
    }
  }

  const definirEscala = (e: Escala) => {
    escala.value = e
    aplicar()
  }
  const alternarContraste = () => {
    altoContraste.value = !altoContraste.value
    aplicar()
  }
  const definirTema = (t: Tema) => {
    tema.value = t
    aplicar()
  }
  const restaurar = () => {
    escala.value = 100
    altoContraste.value = false
    aplicar()
  }

  return {
    escala,
    altoContraste,
    tema,
    aplicar,
    definirEscala,
    alternarContraste,
    definirTema,
    restaurar,
  }
})
