<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { agendamentosApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { hojeIso, hora, intervaloDoDia, rotulo } from '@/lib/formatar'
import type { Agendamento } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const dia = ref(hojeIso())
const itens = ref<Agendamento[]>([])
const msg = ref('')
const lista = useCarga()
const acao = useCarga()
const carregar = () =>
  lista.executar(async () => {
    itens.value = (
      await agendamentosApi.listar({
        ...intervaloDoDia(dia.value),
        per_page: 100,
        ordem: 'inicio_em',
      })
    ).data
  })
onMounted(carregar)

async function mudar(a: Agendamento, qual: 'confirmar' | 'cancelar' | 'faltou'): Promise<void> {
  const ok = await acao.executar(() =>
    agendamentosApi.acao(
      a.id,
      qual,
      qual === 'cancelar' ? { motivo: 'Cancelado pela recepção' } : {},
    ),
  )
  if (ok) {
    msg.value = 'Agendamento atualizado.'
    await carregar()
  }
}
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Agenda do dia</h1>
  <div class="mb-4 flex flex-wrap items-end gap-2">
    <div class="flex flex-col gap-1">
      <label for="dia" class="font-medium">Dia</label>
      <input
        id="dia"
        v-model="dia"
        type="date"
        class="min-h-11 rounded-md border-2 border-border-strong bg-surface px-3"
        @change="carregar"
      />
    </div>
  </div>
  <AlertaMsg v-if="msg" tom="sucesso" class="mb-4">{{ msg }}</AlertaMsg>
  <AlertaMsg v-if="acao.erro.value" tom="erro" class="mb-4">{{ acao.erro.value }}</AlertaMsg>

  <EstadoCarga
    :carregando="lista.carregando.value"
    :erro="lista.erro.value"
    :vazio="!itens.length"
    texto-vazio="Nenhum agendamento neste dia."
  >
    <ul class="flex flex-col gap-3">
      <li v-for="a in itens" :key="a.id" class="rounded-lg border-2 border-border p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-semibold">
            {{ hora(a.inicio_em) }} — {{ a.paciente_nome ?? 'Paciente' }}
          </h2>
          <StatusBadge :status="a.status" />
        </div>
        <p class="text-text-secondary">
          {{ a.profissional_nome ?? 'Profissional' }} · {{ rotulo(a.modalidade) }}
        </p>
        <div v-if="['agendado', 'confirmado'].includes(a.status)" class="mt-3 flex flex-wrap gap-2">
          <BaseButton
            v-if="a.status === 'agendado'"
            variante="secundario"
            @click="mudar(a, 'confirmar')"
            >Confirmar</BaseButton
          >
          <BaseButton variante="secundario" @click="mudar(a, 'faltou')">Marcar falta</BaseButton>
          <BaseButton variante="perigo" @click="mudar(a, 'cancelar')">Cancelar</BaseButton>
        </div>
      </li>
    </ul>
  </EstadoCarga>
</template>
