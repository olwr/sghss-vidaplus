<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useForm } from 'vee-validate'
import { z } from 'zod'
import { agendamentosApi, clinicoApi, pacientesApi } from '@/api/servicos'
import { errosPorCampo, mensagemDe } from '@/api/http'
import { toTypedSchema } from '@/lib/schemaZod'
import { useCarga } from '@/composables/useCarga'
import { dataHora } from '@/lib/formatar'
import type { Agendamento, Prontuario } from '@/types/api'
import EstadoCarga from '@/components/ui/EstadoCarga.vue'
import CampoTexto from '@/components/ui/CampoTexto.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const id = String(useRoute().params.id)
const ag = ref<Agendamento | null>(null)
const pront = ref<Prontuario | null>(null)
const msg = ref('')
const erroEnvio = ref('')
const carga = useCarga()
const acao = useCarga()

const schema = toTypedSchema(
  z.object({
    queixa_principal: z.string().min(3, 'Descreva a queixa.'),
    diagnostico: z.string().optional(),
    conduta: z.string().optional(),
  }),
)
const { handleSubmit, setErrors, resetForm } = useForm({ validationSchema: schema })

async function carregar(): Promise<void> {
  await carga.executar(async () => {
    ag.value =
      (await agendamentosApi.listar({ per_page: 100 })).data.find((a) => a.id === id) ?? null
    if (ag.value?.paciente_id) pront.value = await pacientesApi.prontuario(ag.value.paciente_id)
  })
}
onMounted(carregar)

const iniciar = async () => {
  if (await acao.executar(() => agendamentosApi.acao(id, 'iniciar'))) await carregar()
}
const concluir = async () => {
  if (await acao.executar(() => agendamentosApi.acao(id, 'concluir'))) {
    msg.value = 'Atendimento concluído.'
    await carregar()
  }
}

const salvar = handleSubmit(async (v) => {
  erroEnvio.value = ''
  if (!pront.value) return
  try {
    await clinicoApi.registrar(pront.value.id, v)
    msg.value = 'Registro salvo no prontuário.'
    resetForm()
    await carregar()
  } catch (e) {
    setErrors(errosPorCampo(e))
    erroEnvio.value = mensagemDe(e)
  }
})
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Atendimento</h1>
  <AlertaMsg v-if="msg" tom="sucesso" class="mb-4">{{ msg }}</AlertaMsg>
  <AlertaMsg v-if="acao.erro.value" tom="erro" class="mb-4">{{ acao.erro.value }}</AlertaMsg>

  <EstadoCarga
    :carregando="carga.carregando.value"
    :erro="carga.erro.value"
    :vazio="!ag"
    texto-vazio="Consulta não encontrada."
  >
    <template v-if="ag">
      <p class="mb-4 text-lg">
        <strong>{{ ag.paciente_nome }}</strong> · {{ dataHora(ag.inicio_em) }}
      </p>
      <div class="mb-6 flex flex-wrap gap-2">
        <BaseButton
          v-if="['agendado', 'confirmado'].includes(ag.status)"
          :carregando="acao.carregando.value"
          @click="iniciar"
          >Iniciar atendimento</BaseButton
        >
        <BaseButton
          v-if="ag.status === 'em_atendimento'"
          variante="secundario"
          :carregando="acao.carregando.value"
          @click="concluir"
          >Concluir atendimento</BaseButton
        >
      </div>

      <form
        v-if="ag.status === 'em_atendimento' && pront"
        class="mb-8 flex max-w-xl flex-col gap-4"
        novalidate
        @submit.prevent="salvar"
      >
        <h2 class="text-xl font-semibold">Novo registro clínico</h2>
        <AlertaMsg v-if="erroEnvio" tom="erro">{{ erroEnvio }}</AlertaMsg>
        <CampoTexto name="queixa_principal" label="Queixa principal" obrigatorio />
        <CampoTexto name="diagnostico" label="Diagnóstico" />
        <CampoTexto name="conduta" label="Conduta" />
        <BaseButton type="submit">Salvar registro</BaseButton>
      </form>

      <section v-if="pront?.registros.length" aria-labelledby="hist">
        <h2 id="hist" class="mb-2 text-xl font-semibold">Registros anteriores</h2>
        <ol class="flex flex-col gap-2">
          <li
            v-for="r in pront.registros"
            :key="r.id"
            class="rounded-md border-2 border-border p-3"
          >
            <strong>{{ dataHora(r.registrado_em) }}</strong>
            <p v-if="r.queixa_principal">{{ r.queixa_principal }}</p>
            <p v-if="r.diagnostico" class="text-text-secondary">{{ r.diagnostico }}</p>
          </li>
        </ol>
      </section>
    </template>
  </EstadoCarga>
</template>
