<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useForm } from 'vee-validate'
import { z } from 'zod'
import { toTypedSchema } from '@/lib/schemaZod'
import { mensagemDe } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import CampoTexto from '@/components/ui/CampoTexto.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const erro = ref('')

const schema = toTypedSchema(
  z.object({
    email: z.string().min(1, 'Informe o e-mail.').email('E-mail inválido.'),
    password: z.string().min(1, 'Informe a senha.'),
  }),
)
const { handleSubmit, isSubmitting } = useForm({ validationSchema: schema })

const entrar = handleSubmit(async (v) => {
  erro.value = ''
  try {
    await auth.entrar(v.email, v.password)
    const destino =
      typeof route.query.destino === 'string' && route.query.destino.startsWith('/')
        ? route.query.destino
        : auth.inicio
    await router.push(destino)
  } catch (e) {
    erro.value = mensagemDe(e)
  }
})
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Entrar</h1>

  <AlertaMsg v-if="route.query.inativo" tom="aviso" class="mb-4"
    >Sua sessão foi encerrada por inatividade. Entre novamente.</AlertaMsg
  >
  <AlertaMsg v-else-if="route.query.expirada" tom="aviso" class="mb-4"
    >Sua sessão expirou. Entre novamente.</AlertaMsg
  >
  <AlertaMsg v-if="erro" tom="erro" class="mb-4">{{ erro }}</AlertaMsg>

  <form class="flex flex-col gap-4" novalidate @submit.prevent="entrar">
    <CampoTexto
      name="email"
      label="E-mail"
      type="email"
      autocomplete="username"
      inputmode="email"
      obrigatorio
    />
    <CampoTexto
      name="password"
      label="Senha"
      type="password"
      autocomplete="current-password"
      obrigatorio
    />
    <BaseButton type="submit" :carregando="isSubmitting">Entrar</BaseButton>
  </form>

  <p class="mt-6">
    Ainda não tem conta?
    <RouterLink to="/registro" class="font-semibold text-link underline"
      >Criar conta de paciente</RouterLink
    >
  </p>
</template>
