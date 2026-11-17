<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { unidadesApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { rotulo } from '@/lib/formatar'
import type { Unidade } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'

const itens = ref<Unidade[]>([])
const { carregando, erro, executar } = useCarga()
onMounted(() =>
  executar(async () => {
    itens.value = await unidadesApi.listar()
  }),
)
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Unidades</h1>
  <EstadoCarga :carregando="carregando" :erro="erro" :vazio="!itens.length">
    <ul class="flex flex-col gap-3">
      <li v-for="u in itens" :key="u.id" class="rounded-lg border-2 border-border p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-semibold">{{ u.nome }}</h2>
          <StatusBadge v-if="u.status" :status="u.status" />
        </div>
        <p class="text-text-secondary">{{ rotulo(u.tipo) }} · {{ u.cnpj }}</p>
      </li>
    </ul>
  </EstadoCarga>
</template>
