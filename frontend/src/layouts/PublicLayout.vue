<script setup lang="ts">
import { RouterView } from 'vue-router'
import PainelAcessibilidade from '@/components/a11y/PainelAcessibilidade.vue'
</script>

<template>
  <a href="#conteudo" class="sr-only-focusable rounded bg-primary p-3 text-on-primary"
    >Ir para o conteúdo</a
  >
  <header
    class="flex flex-wrap items-center justify-between gap-3 border-b-2 border-border bg-surface px-4 py-3"
  >
    <p class="text-xl font-bold text-primary">
      VidaPlus <span class="font-normal text-text-secondary">SGHSS</span>
    </p>
    <PainelAcessibilidade />
  </header>
  <main id="conteudo" tabindex="-1" class="mx-auto w-full max-w-xl p-4 outline-none">
    <RouterView />
  </main>
</template>
