<script setup lang="ts">
// min-h-11 = 44 px (alvo de toque); desabilitado tem estilo próprio, sem usar text-muted (comentário fora do <template>: comentário na raiz vira fragmento e quebra class/attrs)
withDefaults(
  defineProps<{
    variante?: 'primario' | 'secundario' | 'perigo' | 'texto'
    type?: 'button' | 'submit'
    carregando?: boolean
    desabilitado?: boolean
  }>(),
  { variante: 'primario', type: 'button', carregando: false, desabilitado: false },
)

const estilos = {
  primario:
    'bg-primary text-on-primary hover:bg-primary-hover active:bg-primary-pressed border-2 border-transparent',
  secundario: 'bg-surface text-text border-2 border-border-strong hover:bg-surface-alt',
  perigo: 'bg-danger-bg text-danger border-2 border-danger hover:opacity-90',
  texto: 'bg-transparent text-link underline border-2 border-transparent hover:bg-surface-alt',
} as const
</script>

<template>
  <button
    :type="type"
    :disabled="desabilitado || carregando"
    :aria-busy="carregando"
    :class="[
      'inline-flex min-h-11 items-center justify-center gap-2 rounded-md px-4 py-2 font-semibold',
      'disabled:cursor-not-allowed disabled:opacity-60',
      estilos[variante],
    ]"
  >
    <span
      v-if="carregando"
      aria-hidden="true"
      class="inline-block size-4 animate-spin rounded-full border-2 border-current border-t-transparent"
    />
    <slot />
  </button>
</template>
