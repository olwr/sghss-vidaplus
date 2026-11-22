<script setup lang="ts">
import AlertaMsg from './AlertaMsg.vue'

defineProps<{ carregando: boolean; erro?: string; vazio?: boolean; textoVazio?: string }>()
</script>

<template>
  <p v-if="carregando" role="status" class="py-6 text-text-secondary">Carregando…</p>
  <AlertaMsg v-else-if="erro" tom="erro">{{ erro }}</AlertaMsg>
  <p v-else-if="vazio" class="py-6 text-text-secondary">
    {{ textoVazio ?? 'Nenhum registro encontrado.' }}
  </p>
  <slot v-else />
</template>
