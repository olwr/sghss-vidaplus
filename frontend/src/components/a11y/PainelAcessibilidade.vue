<script setup lang="ts">
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { ESCALAS, useAcessibilidadeStore } from '@/stores/acessibilidade'
import BaseButton from '@/components/ui/BaseButton.vue'

const a11y = useAcessibilidadeStore()
</script>

<template>
  <Disclosure v-slot="{ open }" as="div" class="relative">
    <DisclosureButton
      class="inline-flex min-h-11 items-center gap-2 rounded-md border-2 border-border-strong bg-surface px-3 py-2 font-semibold text-text"
    >
      <span aria-hidden="true">Aa</span> Acessibilidade
      <span class="sr-only">{{ open ? '(fechar painel)' : '(abrir painel)' }}</span>
    </DisclosureButton>

    <DisclosurePanel
      class="absolute right-0 z-40 mt-2 w-80 max-w-[90vw] rounded-lg border-2 border-border-strong bg-surface p-4 text-text shadow-lg"
    >
      <fieldset class="mb-4">
        <legend class="mb-2 font-semibold">Tamanho do texto</legend>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Tamanho do texto">
          <BaseButton
            v-for="e in ESCALAS"
            :key="e"
            :variante="a11y.escala === e ? 'primario' : 'secundario'"
            :aria-pressed="a11y.escala === e"
            @click="a11y.definirEscala(e)"
          >
            {{ e }}%
          </BaseButton>
        </div>
      </fieldset>

      <div class="mb-4 flex flex-col gap-2">
        <BaseButton
          :variante="a11y.altoContraste ? 'primario' : 'secundario'"
          :aria-pressed="a11y.altoContraste"
          @click="a11y.alternarContraste()"
        >
          Alto contraste: {{ a11y.altoContraste ? 'ligado' : 'desligado' }}
        </BaseButton>
        <BaseButton
          variante="secundario"
          :aria-pressed="a11y.tema === 'dark'"
          @click="a11y.definirTema(a11y.tema === 'dark' ? 'light' : 'dark')"
        >
          Tema: {{ a11y.tema === 'dark' ? 'escuro' : 'claro' }}
        </BaseButton>
      </div>

      <BaseButton variante="texto" @click="a11y.restaurar()">Restaurar padrão</BaseButton>
    </DisclosurePanel>
  </Disclosure>
</template>
