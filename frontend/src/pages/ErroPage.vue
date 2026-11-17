<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

defineProps<{ codigo: 403 | 404 }>()
const auth = useAuthStore()
</script>

<template>
  <h1 class="mb-2 text-2xl font-bold text-primary">
    {{ codigo === 403 ? 'Acesso negado' : 'Página não encontrada' }}
  </h1>
  <p class="mb-4 text-text-secondary">
    {{
      codigo === 403
        ? 'Seu perfil não tem permissão para ver esta página.'
        : 'O endereço não existe ou foi movido.'
    }}
  </p>
  <RouterLink
    :to="auth.autenticado ? auth.inicio : '/login'"
    class="font-semibold text-link underline"
    >Voltar ao início</RouterLink
  >
</template>
