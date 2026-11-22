<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useForm } from 'vee-validate'
import { z } from 'zod'
import { toTypedSchema } from '@/lib/schemaZod'
import { cpfValido, formatarCpf } from '@/lib/cpf'
import { authApi } from '@/api/servicos'
import { errosPorCampo, mensagemDe } from '@/api/http'
import type { UnidadeResumo } from '@/types/api'
import CampoTexto from '@/components/ui/CampoTexto.vue'
import CampoSelecao from '@/components/ui/CampoSelecao.vue'
import CampoCheckbox from '@/components/ui/CampoCheckbox.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const router = useRouter()
const unidades = ref<UnidadeResumo[]>([])
const erro = ref('')

// espelha as regras do back-end (RegistroRequest). A API continua sendo a fonte da verdade.
const schema = toTypedSchema(
  z
    .object({
      nome: z.string().min(3, 'Informe o nome completo.').max(150),
      email: z.string().min(1, 'Informe o e-mail.').email('E-mail inválido.'),
      cpf: z.string().refine(cpfValido, 'CPF inválido.'),
      data_nascimento: z.string().min(1, 'Informe a data de nascimento.'),
      sexo: z.string().min(1, 'Selecione uma opção.'),
      telefone: z.string().optional(),
      unidade_id: z.string().min(1, 'Selecione a unidade.'),
      password: z
        .string()
        .min(10, 'A senha precisa ter ao menos 10 caracteres.')
        .regex(/[A-Za-z]/, 'Inclua ao menos uma letra.')
        .regex(/\d/, 'Inclua ao menos um número.'),
      password_confirmation: z.string(),
      consentimento: z
        .boolean()
        .refine((v) => v, 'É necessário aceitar o tratamento dos dados para o atendimento.'),
    })
    .refine((v) => v.password === v.password_confirmation, {
      path: ['password_confirmation'],
      message: 'As senhas não conferem.',
    }),
)

const { handleSubmit, isSubmitting, setErrors, setFieldValue, values } = useForm({
  validationSchema: schema,
  initialValues: { consentimento: false },
})

onMounted(async () => {
  try {
    unidades.value = await authApi.unidades()
  } catch (e) {
    erro.value = mensagemDe(e)
  }
})

const registrar = handleSubmit(async (v) => {
  erro.value = ''
  try {
    await authApi.registrar({ ...v, cpf: v.cpf.replace(/\D/g, '') })
    await router.push({ name: 'login', query: { criada: '1' } })
  } catch (e) {
    setErrors(errosPorCampo(e)) // 422/409 do servidor, por campo
    erro.value = mensagemDe(e)
  }
})
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Criar conta de paciente</h1>
  <AlertaMsg v-if="erro" tom="erro" class="mb-4">{{ erro }}</AlertaMsg>

  <form class="flex flex-col gap-4" novalidate @submit.prevent="registrar">
    <CampoTexto name="nome" label="Nome completo" autocomplete="name" obrigatorio />
    <CampoTexto
      name="email"
      label="E-mail"
      type="email"
      autocomplete="email"
      inputmode="email"
      obrigatorio
    />
    <CampoTexto
      name="cpf"
      label="CPF"
      inputmode="numeric"
      :maxlength="14"
      obrigatorio
      dica="Somente números; usamos o CPF apenas para identificar você."
      @input="setFieldValue('cpf', formatarCpf(values.cpf ?? ''))"
    />
    <CampoTexto
      name="data_nascimento"
      label="Data de nascimento"
      type="date"
      autocomplete="bday"
      obrigatorio
    />
    <CampoSelecao
      name="sexo"
      label="Sexo"
      obrigatorio
      :opcoes="[
        { valor: 'feminino', texto: 'Feminino' },
        { valor: 'masculino', texto: 'Masculino' },
        { valor: 'outro', texto: 'Outro' },
        { valor: 'nao_informado', texto: 'Prefiro não informar' },
      ]"
    />
    <CampoTexto
      name="telefone"
      label="Telefone (opcional)"
      type="tel"
      autocomplete="tel"
      inputmode="tel"
    />
    <CampoSelecao
      name="unidade_id"
      label="Unidade de atendimento"
      obrigatorio
      :opcoes="unidades.map((u) => ({ valor: u.id, texto: u.nome }))"
    />
    <CampoTexto
      name="password"
      label="Senha"
      type="password"
      autocomplete="new-password"
      obrigatorio
      dica="Mínimo de 10 caracteres, com letras e números."
    />
    <CampoTexto
      name="password_confirmation"
      label="Confirme a senha"
      type="password"
      autocomplete="new-password"
      obrigatorio
    />
    <CampoCheckbox
      name="consentimento"
      label="Concordo com o tratamento dos meus dados de saúde para o atendimento (LGPD)."
    />
    <BaseButton type="submit" :carregando="isSubmitting">Criar conta</BaseButton>
  </form>

  <p class="mt-6">
    Já tem conta?
    <RouterLink to="/login" class="font-semibold text-link underline">Entrar</RouterLink>
  </p>
</template>
