<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { clinicoApi, titularApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { baixar } from '@/lib/arquivo'
import { dataCurta } from '@/lib/formatar'
import type { Receita } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const itens = ref<Receita[]>([])
const lista = useCarga()
const pdf = useCarga()
onMounted(() =>
  lista.executar(async () => {
    itens.value = await titularApi.receitas()
  }),
)

const baixarPdf = (r: Receita) =>
  pdf.executar(async () => baixar(await clinicoApi.pdfReceita(r.id), `receita-${r.id}.pdf`))
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Receitas</h1>
  <AlertaMsg v-if="pdf.erro.value" tom="erro" class="mb-4">{{ pdf.erro.value }}</AlertaMsg>
  <EstadoCarga
    :carregando="lista.carregando.value"
    :erro="lista.erro.value"
    :vazio="!itens.length"
    texto-vazio="Nenhuma receita emitida."
  >
    <ul class="flex flex-col gap-3">
      <li v-for="r in itens" :key="r.id" class="rounded-lg border-2 border-border p-4">
        <h2 class="font-semibold">Emitida em {{ dataCurta(r.emitida_em) }}</h2>
        <ul class="my-2 list-disc pl-6">
          <li v-for="i in r.itens" :key="i.medicamento">
            {{ i.medicamento }} {{ i.dosagem }} — {{ i.posologia }}
          </li>
        </ul>
        <BaseButton variante="secundario" :carregando="pdf.carregando.value" @click="baixarPdf(r)"
          >Baixar PDF</BaseButton
        >
      </li>
    </ul>
  </EstadoCarga>
</template>
