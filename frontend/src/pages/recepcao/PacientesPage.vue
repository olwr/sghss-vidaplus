<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { pacientesApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { formatarCpf } from '@/lib/cpf'
import type { Paciente } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import PaginacaoNav from '@/components/ui/PaginacaoNav.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const itens = ref<Paciente[]>([])
const busca = ref('')
const pagina = ref(1)
const meta = ref({ last_page: 1, total: 0 })
const { carregando, erro, executar } = useCarga()

/** CPF não é pesquisável em claro (blind index): se só há dígitos, busca por CPF; senão por nome. */
const filtro = (t: string): Record<string, string> => {
  const v = t.trim()
  if (!v) return {}
  return /^[\d.\-\s]+$/.test(v) ? { cpf: v.replace(/\D/g, '') } : { nome: v }
}
const carregar = () =>
  executar(async () => {
    const r = await pacientesApi.listar({
      page: pagina.value,
      per_page: 15,
      ...filtro(busca.value),
    })
    itens.value = r.data
    meta.value = r.meta
  })
onMounted(carregar)
function buscar(): void {
  pagina.value = 1
  void carregar()
}
function ir(n: number): void {
  pagina.value = n
  void carregar()
}
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Pacientes</h1>
  <form role="search" class="mb-4 flex flex-wrap items-end gap-2" @submit.prevent="buscar">
    <div class="flex flex-col gap-1">
      <label for="busca" class="font-medium">Nome ou CPF</label>
      <input
        id="busca"
        v-model="busca"
        class="min-h-11 rounded-md border-2 border-border-strong bg-surface px-3"
      />
    </div>
    <BaseButton type="submit">Buscar</BaseButton>
  </form>

  <EstadoCarga :carregando="carregando" :erro="erro" :vazio="!itens.length">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left">
        <caption class="sr-only">
          Lista de pacientes
        </caption>
        <thead>
          <tr class="border-b-2 border-border-strong">
            <th scope="col" class="p-2">Nome</th>
            <th scope="col" class="p-2">CPF</th>
            <th scope="col" class="p-2">Ação</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in itens" :key="p.id" class="border-b border-border">
            <th scope="row" class="p-2 font-medium">{{ p.nome }}</th>
            <td class="p-2">{{ p.cpf ? formatarCpf(p.cpf) : '—' }}</td>
            <td class="p-2">
              <RouterLink
                :to="{ name: 'recepcao-agendar', query: { paciente: p.id } }"
                class="inline-flex min-h-11 items-center underline text-link"
                >Agendar consulta<span class="sr-only"> para {{ p.nome }}</span></RouterLink
              >
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginacaoNav :pagina="pagina" :ultima="meta.last_page" :total="meta.total" @ir="ir" />
  </EstadoCarga>
</template>
