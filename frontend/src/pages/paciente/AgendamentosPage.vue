<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { agendamentosApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { dataHora, rotulo } from '@/lib/formatar'
import type { Agendamento } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const itens = ref<Agendamento[]>([])
const alvo = ref<Agendamento | null>(null)
const msg = ref('')
const lista = useCarga()
const acao = useCarga()

const carregar = () =>
  lista.executar(async () => {
    itens.value = (await agendamentosApi.listar({ per_page: 100 })).data
  })
onMounted(carregar)

const podeCancelar = (a: Agendamento) => ['agendado', 'confirmado'].includes(a.status)

async function cancelar(): Promise<void> {
  if (!alvo.value) return
  const id = alvo.value.id
  const ok = await acao.executar(() =>
    agendamentosApi.acao(id, 'cancelar', { motivo: 'Cancelado pelo paciente' }),
  )
  alvo.value = null
  if (ok) {
    msg.value = 'Consulta cancelada.'
    await carregar()
  }
}
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Meus agendamentos</h1>
  <AlertaMsg v-if="msg" tom="sucesso" class="mb-4">{{ msg }}</AlertaMsg>
  <AlertaMsg v-if="acao.erro.value" tom="erro" class="mb-4">{{ acao.erro.value }}</AlertaMsg>

  <EstadoCarga
    :carregando="lista.carregando.value"
    :erro="lista.erro.value"
    :vazio="!itens.length"
    texto-vazio="Nenhum agendamento ainda."
  >
    <ul class="flex flex-col gap-3">
      <li v-for="a in itens" :key="a.id" class="rounded-lg border-2 border-border p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="text-lg font-semibold">{{ dataHora(a.inicio_em) }}</h2>
          <StatusBadge :status="a.status" />
        </div>
        <p class="text-text-secondary">
          {{ a.profissional_nome ?? 'Profissional' }} · {{ a.unidade_nome ?? 'Unidade' }} ·
          {{ rotulo(a.modalidade) }}
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
          <RouterLink
            v-if="a.modalidade === 'telemedicina' && a.sala_telemedicina_id && podeCancelar(a)"
            :to="`/paciente/teleconsulta/${a.sala_telemedicina_id}`"
            class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 font-semibold text-on-primary"
            >Entrar na teleconsulta</RouterLink
          >
          <BaseButton v-if="podeCancelar(a)" variante="perigo" @click="alvo = a"
            >Cancelar</BaseButton
          >
        </div>
      </li>
    </ul>
  </EstadoCarga>

  <ConfirmDialog
    :aberto="!!alvo"
    titulo="Cancelar esta consulta?"
    descricao="Essa ação não pode ser desfeita."
    confirmar="Sim, cancelar"
    perigoso
    :carregando="acao.carregando.value"
    @confirmar="cancelar"
    @cancelar="alvo = null"
  />
</template>
