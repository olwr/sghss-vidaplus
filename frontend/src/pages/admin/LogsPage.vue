<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { adminApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { dataHora, rotulo } from '@/lib/formatar'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'

interface Linha {
  id: string | number
  quando: string
  descricao?: string
  evento?: string
  acao?: string
  recurso?: string
  entidade?: string
  usuario?: string | null
}
const fonte = ref<'atividades' | 'acessos'>('atividades')
const itens = ref<Linha[]>([])
const { carregando, erro, executar } = useCarga()
const carregar = () =>
  executar(async () => {
    const r = (await (fonte.value === 'atividades'
      ? adminApi.atividades({ per_page: 50 })
      : adminApi.acessos({ per_page: 50 }))) as { data: Linha[] }
    itens.value = r.data
  })
onMounted(carregar)
watch(fonte, carregar)
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Auditoria</h1>
  <div class="mb-4 flex flex-col gap-1">
    <label for="fonte" class="font-medium">Tipo de log</label>
    <select
      id="fonte"
      v-model="fonte"
      class="min-h-11 max-w-xs rounded-md border-2 border-border-strong bg-surface px-3"
    >
      <option value="atividades">Atividades</option>
      <option value="acessos">Acessos a dados sensíveis</option>
    </select>
  </div>
  <EstadoCarga :carregando="carregando" :erro="erro" :vazio="!itens.length">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left">
        <caption class="sr-only">
          Registros de auditoria
        </caption>
        <thead>
          <tr class="border-b-2 border-border-strong">
            <th scope="col" class="p-2">Quando</th>
            <th scope="col" class="p-2">Quem</th>
            <th scope="col" class="p-2">O quê</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="l in itens" :key="l.id" class="border-b border-border">
            <td class="p-2">{{ dataHora(l.quando) }}</td>
            <td class="p-2">{{ l.usuario ?? '—' }}</td>
            <td class="p-2">
              {{ l.descricao ?? `${rotulo(l.acao ?? '')} · ${rotulo(l.recurso ?? '')}` }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </EstadoCarga>
</template>
