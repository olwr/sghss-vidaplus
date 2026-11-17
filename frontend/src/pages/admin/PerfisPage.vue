<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { adminApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { rotulo } from '@/lib/formatar'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'

const perfis = ref<{ perfil: string; permissoes: string[] }[]>([])
const { carregando, erro, executar } = useCarga()
onMounted(() =>
  executar(async () => {
    perfis.value = await adminApi.perfis()
  }),
)
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Perfis e permissões</h1>
  <p class="mb-4 text-text-secondary">
    Somente leitura. A autorização real é sempre feita pela API.
  </p>
  <EstadoCarga :carregando="carregando" :erro="erro" :vazio="!perfis.length">
    <Disclosure
      v-for="p in perfis"
      :key="p.perfil"
      v-slot="{ open }"
      as="div"
      class="mb-2 rounded-lg border-2 border-border"
    >
      <DisclosureButton
        class="flex min-h-11 w-full items-center justify-between p-3 text-left font-semibold"
      >
        {{ rotulo(p.perfil) }} ({{ p.permissoes.length }})<span aria-hidden="true">{{
          open ? '▲' : '▼'
        }}</span>
      </DisclosureButton>
      <DisclosurePanel as="ul" class="grid gap-1 p-3 pt-0 sm:grid-cols-2"
        ><li v-for="x in p.permissoes" :key="x" class="font-mono text-sm">
          {{ x }}
        </li></DisclosurePanel
      >
    </Disclosure>
  </EstadoCarga>
</template>
