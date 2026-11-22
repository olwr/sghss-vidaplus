<script setup lang="ts">
// Headless UI: foco preso no diálogo, Esc fecha, foco volta ao botão que abriu (ARIA pronto) (comentário fora do <template>: comentário na raiz vira fragmento e quebra class/attrs)
import { Dialog, DialogPanel, DialogTitle, DialogDescription } from '@headlessui/vue'
import BaseButton from './BaseButton.vue'

defineProps<{
  aberto: boolean
  titulo: string
  descricao?: string
  confirmar?: string
  perigoso?: boolean
  carregando?: boolean
}>()
const emit = defineEmits<{ confirmar: []; cancelar: [] }>()
</script>

<template>
  <Dialog :open="aberto" class="relative z-50" @close="emit('cancelar')">
    <div class="fixed inset-0 bg-black/60" aria-hidden="true" />
    <div class="fixed inset-0 flex items-center justify-center p-4">
      <DialogPanel
        class="w-full max-w-md rounded-lg border-2 border-border-strong bg-surface p-6 text-text"
      >
        <DialogTitle class="text-xl font-bold">{{ titulo }}</DialogTitle>
        <DialogDescription v-if="descricao" class="mt-2 text-text-secondary">{{
          descricao
        }}</DialogDescription>
        <div class="mt-4"><slot /></div>
        <div class="mt-6 flex flex-wrap justify-end gap-3">
          <BaseButton variante="secundario" @click="emit('cancelar')">Voltar</BaseButton>
          <BaseButton
            :variante="perigoso ? 'perigo' : 'primario'"
            :carregando="carregando"
            @click="emit('confirmar')"
          >
            {{ confirmar ?? 'Confirmar' }}
          </BaseButton>
        </div>
      </DialogPanel>
    </div>
  </Dialog>
</template>
