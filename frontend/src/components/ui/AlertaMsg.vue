<script setup lang="ts">
// erro = role=alert (anunciado na hora); demais = role=status (educado) (comentário fora do <template>: comentário na raiz vira fragmento e quebra class/attrs)
const props = withDefaults(defineProps<{ tom?: 'erro' | 'sucesso' | 'aviso' | 'info' }>(), {
  tom: 'info',
})
const TONS = {
  erro: { classe: 'bg-danger-bg text-danger border-danger', icone: '⚠', papel: 'alert' },
  sucesso: { classe: 'bg-success-bg text-success border-success', icone: '✔', papel: 'status' },
  aviso: { classe: 'bg-warning-bg text-warning border-warning-icon', icone: '!', papel: 'status' },
  info: { classe: 'bg-info-bg text-info border-info', icone: 'ℹ', papel: 'status' },
} as const
</script>

<template>
  <div
    :role="TONS[props.tom].papel"
    :class="['flex items-start gap-2 rounded-md border-2 p-3', TONS[props.tom].classe]"
  >
    <span aria-hidden="true">{{ TONS[props.tom].icone }}</span>
    <div><slot /></div>
  </div>
</template>
