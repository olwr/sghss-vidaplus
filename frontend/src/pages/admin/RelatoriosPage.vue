<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { adminApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import type { RelatorioDados } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import GraficoBarras from '@/components/GraficoBarras.vue'

const TIPOS = [
  { valor: 'consultas', rotulo: 'Consultas' },
  { valor: 'ocupacao-leitos', rotulo: 'Ocupação de leitos' },
  { valor: 'internacoes', rotulo: 'Internações' },
  { valor: 'financeiro', rotulo: 'Financeiro' },
  { valor: 'estoque-critico', rotulo: 'Estoque crítico' },
]
const tipo = ref(TIPOS[0]!.valor)
const dados = ref<RelatorioDados | null>(null)
const { carregando, erro, executar } = useCarga()
// rótulo = colunas de texto da linha; valor = a ÚLTIMA coluna numérica (cada relatório tem colunas diferentes)
const rotuloDe = (l: (string | number | null)[]): string =>
  l
    .slice(0, -1)
    .filter((c) => c !== null)
    .join(' · ')
const valorDe = (l: (string | number | null)[]): number => Number(l[l.length - 1] ?? 0) || 0
const carregar = () =>
  executar(async () => {
    dados.value = await adminApi.relatorio(tipo.value)
  })
onMounted(carregar)
watch(tipo, carregar)
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Relatórios</h1>
  <div class="mb-4 flex flex-col gap-1">
    <label for="tipo" class="font-medium">Relatório</label>
    <select
      id="tipo"
      v-model="tipo"
      class="min-h-11 max-w-xs rounded-md border-2 border-border-strong bg-surface px-3"
    >
      <option v-for="t in TIPOS" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
    </select>
  </div>
  <EstadoCarga :carregando="carregando" :erro="erro" :vazio="!dados?.linhas.length">
    <template v-if="dados">
      <GraficoBarras
        :rotulos="dados.linhas.map(rotuloDe)"
        :valores="dados.linhas.map(valorDe)"
        :titulo="dados.titulo"
      />
      <!-- Tabela = alternativa textual do gráfico (WCAG 1.1.1) -->
      <div class="mt-4 overflow-x-auto">
        <table class="w-full border-collapse text-left">
          <caption class="mb-2 text-left font-semibold">
            {{
              dados.titulo
            }}
          </caption>
          <thead>
            <tr class="border-b-2 border-border-strong">
              <th v-for="c in dados.colunas" :key="c" scope="col" class="p-2">{{ c }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(l, i) in dados.linhas" :key="i" class="border-b border-border">
              <td v-for="(c, j) in l" :key="j" class="p-2">{{ c }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </EstadoCarga>
</template>
