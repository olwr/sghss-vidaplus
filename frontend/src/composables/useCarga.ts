import { ref } from 'vue'
import { mensagemDe } from '@/api/http'

/** Executa uma chamada de API expondo carregando/erro (mensagem em português do problem+json). */
export function useCarga() {
  const carregando = ref(false)
  const erro = ref('')

  async function executar<T>(fn: () => Promise<T>): Promise<T | undefined> {
    carregando.value = true
    erro.value = ''
    try {
      return await fn()
    } catch (e) {
      erro.value = mensagemDe(e)
      return undefined
    } finally {
      carregando.value = false
    }
  }

  return { carregando, erro, executar }
}
