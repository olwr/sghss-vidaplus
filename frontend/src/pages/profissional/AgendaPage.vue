<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { agendamentosApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { hojeIso, hora, intervaloDoDia, rotulo } from '@/lib/formatar'
import type { Agendamento } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'

const dia = ref(hojeIso())
const itens = ref<Agendamento[]>([])
const { carregando, erro, executar } = useCarga()
const carregar = () =>
  executar(async () => {
    itens.value = (
      await agendamentosApi.listar({
        ...intervaloDoDia(dia.value),
        per_page: 100,
        ordem: 'inicio_em',
      })
    ).data
  })
onMounted(carregar)
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Minha agenda</h1>
  <div class="mb-4 flex flex-col gap-1">
    <label for="dia" class="font-medium">Dia</label>
    <input
      id="dia"
      v-model="dia"
      type="date"
      class="min-h-11 max-w-xs rounded-md border-2 border-border-strong bg-surface px-3"
      @change="carregar"
    />
  </div>
  <EstadoCarga
    :carregando="carregando"
    :erro="erro"
    :vazio="!itens.length"
    texto-vazio="Nenhuma consulta neste dia."
  >
    <ul class="flex flex-col gap-3">
      <li v-for="a in itens" :key="a.id" class="rounded-lg border-2 border-border p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-semibold">
            {{ hora(a.inicio_em) }} — {{ a.paciente_nome ?? 'Paciente' }}
          </h2>
          <StatusBadge :status="a.status" />
        </div>
        <p class="text-text-secondary">{{ rotulo(a.modalidade) }}</p>
        <div
          v-if="['agendado', 'confirmado', 'em_atendimento'].includes(a.status)"
          class="mt-3 flex flex-wrap gap-2"
        >
          <RouterLink
            :to="`/profissional/atendimento/${a.id}`"
            class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 font-semibold text-on-primary"
            >Atender<span class="sr-only"> {{ a.paciente_nome }}</span></RouterLink
          >
          <RouterLink
            v-if="a.modalidade === 'telemedicina' && a.sala_telemedicina_id"
            :to="`/profissional/teleconsulta/${a.sala_telemedicina_id}`"
            class="inline-flex min-h-11 items-center rounded-md border-2 border-border-strong px-4 font-semibold"
            >Abrir teleconsulta</RouterLink
          >
        </div>
      </li>
    </ul>
  </EstadoCarga>
</template>
