<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { clinicoApi, titularApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { baixar, extensaoDe } from '@/lib/arquivo'
import { dataCurta, rotulo } from '@/lib/formatar'
import type { Exame } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const itens = ref<Exame[]>([])
const lista = useCarga()
const arq = useCarga()
onMounted(() =>
  lista.executar(async () => {
    itens.value = await titularApi.exames()
  }),
)

const resumo = ref<{ exame: string; texto: string } | null>(null)

/** Arquivo → baixa com a extensão do tipo real; sem arquivo (JSON com `resumo`) → mostra o texto na tela. */
const baixarResultado = (e: Exame) =>
  arq.executar(async () => {
    const blob = await clinicoApi.resultadoExame(e.id)
    if (blob.type.includes('json')) {
      const corpo = JSON.parse(await blob.text()) as { resumo?: string | null }
      resumo.value = { exame: e.nome, texto: corpo.resumo || 'Resultado sem resumo disponível.' }
      return
    }
    baixar(blob, `resultado-${e.id}.${extensaoDe(blob.type)}`)
  })
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Resultados de exames</h1>
  <AlertaMsg v-if="arq.erro.value" tom="erro" class="mb-4">{{ arq.erro.value }}</AlertaMsg>
  <section
    v-if="resumo"
    aria-labelledby="res-t"
    class="mb-4 rounded-lg border-2 border-primary p-4"
  >
    <h2 id="res-t" class="font-semibold">Resultado: {{ resumo.exame }}</h2>
    <p class="mt-1 whitespace-pre-line">{{ resumo.texto }}</p>
    <BaseButton class="mt-3" variante="secundario" @click="resumo = null">Fechar</BaseButton>
  </section>
  <EstadoCarga
    :carregando="lista.carregando.value"
    :erro="lista.erro.value"
    :vazio="!itens.length"
    texto-vazio="Nenhum exame solicitado."
  >
    <ul class="flex flex-col gap-3">
      <li v-for="e in itens" :key="e.id" class="rounded-lg border-2 border-border p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-semibold">{{ e.nome }}</h2>
          <StatusBadge :status="e.resultado_disponivel ? 'disponivel' : e.status" />
        </div>
        <p class="text-text-secondary">
          Solicitado em {{ e.solicitado_em ? dataCurta(e.solicitado_em) : '—' }} ·
          {{ rotulo(e.status) }}
        </p>
        <p v-if="e.instrucoes" class="mt-1">{{ e.instrucoes }}</p>
        <BaseButton
          v-if="e.resultado_disponivel"
          class="mt-3"
          variante="secundario"
          :carregando="arq.carregando.value"
          @click="baixarResultado(e)"
          >Ver / baixar resultado</BaseButton
        >
      </li>
    </ul>
  </EstadoCarga>
</template>
