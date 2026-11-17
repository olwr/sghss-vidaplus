<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { adminApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { rotulo } from '@/lib/formatar'
import type { UsuarioAdmin } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import PaginacaoNav from '@/components/ui/PaginacaoNav.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const itens = ref<UsuarioAdmin[]>([])
const pagina = ref(1)
const meta = ref({ last_page: 1, total: 0 })
const msg = ref('')
const lista = useCarga()
const acao = useCarga()
const carregar = () =>
  lista.executar(async () => {
    const r = await adminApi.usuarios({ page: pagina.value, per_page: 15 })
    itens.value = r.data
    meta.value = r.meta
  })
onMounted(carregar)
const ir = (n: number) => {
  pagina.value = n
  void carregar()
}

async function alternar(u: UsuarioAdmin): Promise<void> {
  const ok = await acao.executar(() => adminApi.atualizarUsuario(u.id, { ativo: !u.ativo }))
  if (ok) {
    msg.value = `Usuário ${u.ativo ? 'desativado' : 'ativado'}.`
    await carregar()
  }
}
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Usuários</h1>
  <AlertaMsg v-if="msg" tom="sucesso" class="mb-4">{{ msg }}</AlertaMsg>
  <AlertaMsg v-if="acao.erro.value" tom="erro" class="mb-4">{{ acao.erro.value }}</AlertaMsg>
  <EstadoCarga :carregando="lista.carregando.value" :erro="lista.erro.value" :vazio="!itens.length">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left">
        <caption class="sr-only">
          Usuários do sistema
        </caption>
        <thead>
          <tr class="border-b-2 border-border-strong">
            <th scope="col" class="p-2">Nome</th>
            <th scope="col" class="p-2">Perfil</th>
            <th scope="col" class="p-2">Situação</th>
            <th scope="col" class="p-2">Ação</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in itens" :key="u.id" class="border-b border-border">
            <th scope="row" class="p-2 font-medium">
              {{ u.nome
              }}<span class="block text-sm font-normal text-text-secondary">{{ u.email }}</span>
            </th>
            <td class="p-2">{{ u.perfis.map(rotulo).join(', ') }}</td>
            <td class="p-2">{{ u.ativo ? '✔ Ativo' : '✖ Inativo' }}</td>
            <td class="p-2">
              <BaseButton variante="secundario" @click="alternar(u)"
                >{{ u.ativo ? 'Desativar' : 'Ativar'
                }}<span class="sr-only"> {{ u.nome }}</span></BaseButton
              >
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginacaoNav :pagina="pagina" :ultima="meta.last_page" :total="meta.total" @ir="ir" />
  </EstadoCarga>
</template>
