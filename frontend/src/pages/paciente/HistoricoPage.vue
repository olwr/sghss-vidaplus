<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { pacientesApi, titularApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { dataHora } from '@/lib/formatar'
import type { Prontuario } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'

const pront = ref<Prontuario | null>(null)
const { carregando, erro, executar } = useCarga()

onMounted(() =>
  executar(async () => {
    const eu = await titularApi.meusDados()
    pront.value = await pacientesApi.prontuario(eu.id)
  }),
)
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Histórico clínico</h1>
  <EstadoCarga
    :carregando="carregando"
    :erro="erro"
    :vazio="!pront"
    texto-vazio="Você ainda não tem prontuário."
  >
    <dl v-if="pront" class="mb-6 grid gap-2 sm:grid-cols-2">
      <div>
        <dt class="font-semibold">Tipo sanguíneo</dt>
        <dd>{{ pront.tipo_sanguineo ?? 'Não informado' }}</dd>
      </div>
      <div>
        <dt class="font-semibold">Alergias</dt>
        <dd>{{ pront.alergias ?? 'Nenhuma registrada' }}</dd>
      </div>
    </dl>
    <ol v-if="pront?.registros.length" class="flex flex-col gap-3">
      <li v-for="r in pront.registros" :key="r.id" class="rounded-lg border-2 border-border p-4">
        <h2 class="font-semibold">{{ dataHora(r.registrado_em) }}</h2>
        <p v-if="r.queixa_principal"><strong>Queixa:</strong> {{ r.queixa_principal }}</p>
        <p v-if="r.diagnostico"><strong>Diagnóstico:</strong> {{ r.diagnostico }}</p>
        <p v-if="r.conduta"><strong>Conduta:</strong> {{ r.conduta }}</p>
      </li>
    </ol>
    <p v-else-if="pront" class="text-text-secondary">Nenhum registro clínico ainda.</p>
  </EstadoCarga>
</template>
