<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { agendamentosApi } from '@/api/servicos'
import { useAuthStore } from '@/stores/auth'
import { useCarga } from '@/composables/useCarga'
import { dataHora } from '@/lib/formatar'
import type { Agendamento } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'

const auth = useAuthStore()
const proximas = ref<Agendamento[]>([])
const { carregando, erro, executar } = useCarga()

onMounted(() =>
  executar(async () => {
    const r = await agendamentosApi.listar({ per_page: 50 })
    proximas.value = r.data.filter((a) => ['agendado', 'confirmado'].includes(a.status)).slice(0, 3)
  }),
)

const atalhos = [
  { to: '/paciente/agendar', texto: 'Agendar consulta', icone: '📅' },
  { to: '/paciente/agendamentos', texto: 'Meus agendamentos', icone: '🗂' },
  { to: '/paciente/receitas', texto: 'Receitas', icone: '💊' },
  { to: '/paciente/resultados', texto: 'Resultados de exames', icone: '🧪' },
]
</script>

<template>
  <h1 class="mb-1 text-2xl font-bold text-primary">Olá, {{ auth.usuario?.nome }}</h1>
  <p class="mb-6 text-text-secondary">O que você quer fazer hoje?</p>

  <ul class="mb-8 grid gap-3 sm:grid-cols-2">
    <li v-for="a in atalhos" :key="a.to">
      <RouterLink
        :to="a.to"
        class="flex min-h-16 items-center gap-3 rounded-lg border-2 border-border-strong bg-surface p-4 text-lg font-semibold hover:bg-surface-alt"
      >
        <span aria-hidden="true">{{ a.icone }}</span
        >{{ a.texto }}
      </RouterLink>
    </li>
  </ul>

  <h2 class="mb-2 text-xl font-semibold">Próximas consultas</h2>
  <EstadoCarga
    :carregando="carregando"
    :erro="erro"
    :vazio="!proximas.length"
    texto-vazio="Você não tem consultas marcadas."
  >
    <ul class="flex flex-col gap-2">
      <li
        v-for="a in proximas"
        :key="a.id"
        class="flex flex-wrap items-center justify-between gap-2 rounded-md border-2 border-border p-3"
      >
        <span
          ><strong>{{ dataHora(a.inicio_em) }}</strong> —
          {{ a.profissional_nome ?? 'Profissional' }}</span
        >
        <StatusBadge :status="a.status" />
      </li>
    </ul>
  </EstadoCarga>
</template>
