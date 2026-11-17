<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { pacientesApi, titularApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { rotulo } from '@/lib/formatar'
import type { Equipe } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'

const equipes = ref<Equipe[]>([])
const { carregando, erro, executar } = useCarga()
onMounted(() =>
  executar(async () => {
    const eu = await titularApi.meusDados()
    equipes.value = await pacientesApi.equipe(eu.id)
  }),
)
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Minha equipe de cuidado</h1>
  <EstadoCarga
    :carregando="carregando"
    :erro="erro"
    :vazio="!equipes.length"
    texto-vazio="Você ainda não foi vinculado a uma equipe."
  >
    <section
      v-for="e in equipes"
      :key="e.id"
      class="mb-4 rounded-lg border-2 border-border p-4"
      :aria-labelledby="`eq-${e.id}`"
    >
      <h2 :id="`eq-${e.id}`" class="text-lg font-semibold">{{ e.nome }}</h2>
      <p v-if="e.descricao" class="text-text-secondary">{{ e.descricao }}</p>
      <ul class="mt-2 list-disc pl-6">
        <li v-for="p in e.profissionais ?? []" :key="p.id">
          {{ p.nome ?? 'Profissional' }} — {{ rotulo(p.tipo)
          }}<span v-if="p.funcao"> ({{ p.funcao }})</span>
        </li>
      </ul>
    </section>
  </EstadoCarga>
</template>
