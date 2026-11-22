<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useInatividade } from '@/composables/useInatividade'
import PainelAcessibilidade from '@/components/a11y/PainelAcessibilidade.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const auth = useAuthStore()
const router = useRouter()

interface Item {
  para: string
  texto: string
}
const MENUS: Record<string, Item[]> = {
  paciente: [
    { para: '/paciente', texto: 'Início' },
    { para: '/paciente/agendar', texto: 'Agendar' },
    { para: '/paciente/agendamentos', texto: 'Meus agendamentos' },
    { para: '/paciente/historico', texto: 'Histórico' },
    { para: '/paciente/receitas', texto: 'Receitas' },
    { para: '/paciente/resultados', texto: 'Resultados' },
    { para: '/paciente/equipe', texto: 'Minha equipe' },
    { para: '/paciente/privacidade', texto: 'Privacidade' },
  ],
  secretario: [
    { para: '/recepcao/pacientes', texto: 'Pacientes' },
    { para: '/recepcao/agenda', texto: 'Agenda' },
  ],
  profissional_saude: [{ para: '/profissional/agenda', texto: 'Minha agenda' }],
  administrador: [
    { para: '/admin/relatorios', texto: 'Relatórios' },
    { para: '/admin/usuarios', texto: 'Usuários' },
    { para: '/admin/perfis', texto: 'Perfis' },
    { para: '/admin/unidades', texto: 'Unidades' },
    { para: '/admin/logs', texto: 'Auditoria' },
  ],
}
// O menu só ESCONDE o que o perfil não usa; quem autoriza de verdade é sempre a API.
const menu = computed(() => MENUS[auth.perfil ?? ''] ?? [])

async function sair(): Promise<void> {
  await auth.sair()
  await router.push({ name: 'login' })
}

const { aviso, reiniciar } = useInatividade({
  aoExpirar: () => {
    auth.limpar()
    void router.push({ name: 'login', query: { inativo: '1' } })
  },
})
</script>

<template>
  <a href="#conteudo" class="sr-only-focusable rounded bg-primary p-3 text-on-primary"
    >Ir para o conteúdo</a
  >

  <header
    class="flex flex-wrap items-center justify-between gap-3 border-b-2 border-border bg-surface px-4 py-3"
  >
    <p class="text-xl font-bold text-primary">
      VidaPlus <span class="font-normal text-text-secondary">SGHSS</span>
    </p>
    <div class="flex flex-wrap items-center gap-3">
      <span class="text-text-secondary">{{ auth.usuario?.nome }}</span>
      <PainelAcessibilidade />
      <BaseButton variante="secundario" @click="sair">Sair</BaseButton>
    </div>
  </header>

  <nav aria-label="Principal" class="border-b-2 border-border bg-surface-alt px-4 py-2">
    <ul class="flex flex-wrap gap-2">
      <li v-for="i in menu" :key="i.para">
        <RouterLink
          :to="i.para"
          class="inline-flex min-h-11 items-center rounded-md px-3 font-medium text-link underline"
          active-class="bg-accent-subtle !font-bold"
          exact-active-class="bg-accent-subtle !font-bold"
        >
          {{ i.texto }}
        </RouterLink>
      </li>
    </ul>
  </nav>

  <div v-if="aviso" class="mx-auto max-w-3xl p-4">
    <AlertaMsg tom="aviso">
      <p class="font-semibold">Sua sessão vai expirar por inatividade em 1 minuto.</p>
      <BaseButton class="mt-2" @click="reiniciar">Continuar conectado</BaseButton>
    </AlertaMsg>
  </div>

  <main id="conteudo" tabindex="-1" class="mx-auto w-full max-w-5xl p-4 outline-none">
    <RouterView />
  </main>
</template>
