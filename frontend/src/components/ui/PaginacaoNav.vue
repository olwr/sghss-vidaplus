<script setup lang="ts">
import BaseButton from './BaseButton.vue'

defineProps<{ pagina: number; ultima: number; total: number }>()
const emit = defineEmits<{ ir: [numero: number] }>()
</script>

<template>
  <nav aria-label="Paginação" class="mt-4 flex flex-wrap items-center justify-between gap-3">
    <p class="text-text-secondary" aria-live="polite">
      Página {{ pagina }} de {{ ultima }} · {{ total }} registro(s)
    </p>
    <div class="flex gap-2">
      <BaseButton variante="secundario" :desabilitado="pagina <= 1" @click="emit('ir', pagina - 1)"
        >Anterior</BaseButton
      >
      <BaseButton
        variante="secundario"
        :desabilitado="pagina >= ultima"
        @click="emit('ir', pagina + 1)"
        >Próxima</BaseButton
      >
    </div>
  </nav>
</template>
