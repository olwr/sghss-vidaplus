<script setup lang="ts">
import { computed } from 'vue'
import { rotulo } from '@/lib/formatar'

/** Cor NUNCA é o único sinal (WCAG 1.4.1): todo status leva ícone + texto. */
const props = defineProps<{ status: string }>()

const MAPA: Record<
  string,
  { tom: 'success' | 'warning' | 'danger' | 'info'; icone: string; texto?: string }
> = {
  agendado: { tom: 'info', icone: '🕒', texto: 'Agendado' },
  confirmado: { tom: 'success', icone: '✔', texto: 'Confirmado' },
  em_atendimento: { tom: 'info', icone: '▶', texto: 'Em atendimento' },
  concluido: { tom: 'success', icone: '✔', texto: 'Concluído' },
  cancelado: { tom: 'danger', icone: '✖', texto: 'Cancelado' },
  remarcado: { tom: 'warning', icone: '↻', texto: 'Remarcado' },
  faltou: { tom: 'warning', icone: '!', texto: 'Faltou' },
  ativa: { tom: 'success', icone: '✔', texto: 'Ativa' },
  desativada: { tom: 'danger', icone: '✖', texto: 'Desativada' },
  em_reforma: { tom: 'warning', icone: '🛠', texto: 'Em reforma' },
  em_recesso: { tom: 'info', icone: '⏸', texto: 'Em recesso' },
  emitida: { tom: 'success', icone: '✔', texto: 'Emitida' },
  disponivel: { tom: 'success', icone: '✔', texto: 'Disponível' },
}
const TONS = {
  success: 'bg-success-bg text-success border-success',
  warning: 'bg-warning-bg text-warning border-warning-icon',
  danger: 'bg-danger-bg text-danger border-danger',
  info: 'bg-info-bg text-info border-info',
} as const

const def = computed(() => MAPA[props.status] ?? { tom: 'info' as const, icone: '•' })
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-sm font-semibold',
      TONS[def.tom],
    ]"
  >
    <span aria-hidden="true">{{ def.icone }}</span
    >{{ def.texto ?? rotulo(status) }}
  </span>
</template>
