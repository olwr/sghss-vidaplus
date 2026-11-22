<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { titularApi } from '@/api/servicos'
import { useCarga } from '@/composables/useCarga'
import { baixar } from '@/lib/arquivo'
import { rotulo } from '@/lib/formatar'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

/** LGPD art. 18: o titular vê, concede/revoga consentimentos e exporta seus dados. */
const consentimentos = ref<{ finalidade: string; vigente: boolean }[]>([])
const msg = ref('')
const lista = useCarga()
const acao = useCarga()
const carregar = () =>
  lista.executar(async () => {
    consentimentos.value = await titularApi.consentimentos()
  })
onMounted(carregar)

async function alternar(c: { finalidade: string; vigente: boolean }): Promise<void> {
  const ok = await acao.executar(() =>
    c.vigente ? titularApi.revogar(c.finalidade) : titularApi.conceder(c.finalidade),
  )
  if (ok) {
    msg.value = `Consentimento “${rotulo(c.finalidade)}” ${c.vigente ? 'revogado' : 'concedido'}.`
    await carregar()
  }
}
const exportar = () =>
  acao.executar(async () => baixar(await titularApi.exportar(), 'meus-dados.json'))
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Privacidade e meus dados</h1>
  <AlertaMsg v-if="msg" tom="sucesso" class="mb-4">{{ msg }}</AlertaMsg>
  <AlertaMsg v-if="acao.erro.value" tom="erro" class="mb-4">{{ acao.erro.value }}</AlertaMsg>

  <h2 class="mb-2 text-xl font-semibold">Consentimentos</h2>
  <EstadoCarga
    :carregando="lista.carregando.value"
    :erro="lista.erro.value"
    :vazio="!consentimentos.length"
  >
    <ul class="mb-8 flex flex-col gap-2">
      <li
        v-for="c in consentimentos"
        :key="c.finalidade"
        class="flex flex-wrap items-center justify-between gap-2 rounded-md border-2 border-border p-3"
      >
        <span
          >{{ rotulo(c.finalidade) }} —
          <strong>{{ c.vigente ? '✔ Autorizado' : '✖ Não autorizado' }}</strong></span
        >
        <BaseButton
          variante="secundario"
          :carregando="acao.carregando.value"
          @click="alternar(c)"
          >{{ c.vigente ? 'Revogar' : 'Autorizar' }}</BaseButton
        >
      </li>
    </ul>
  </EstadoCarga>

  <h2 class="mb-2 text-xl font-semibold">Meus dados</h2>
  <p class="mb-3 text-text-secondary">Baixe uma cópia de todos os dados que temos sobre você.</p>
  <BaseButton variante="secundario" :carregando="acao.carregando.value" @click="exportar"
    >Exportar meus dados</BaseButton
  >
</template>
