<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { useRoute } from 'vue-router'
import { telemedicinaApi } from '@/api/servicos'
import { mensagemDe } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

const route = useRoute()
const auth = useAuthStore()
const sala = String(route.params.sala)

type Etapa = 'verificar' | 'pronto' | 'sala' | 'encerrada'
const etapa = ref<Etapa>('verificar')
const camera = ref(false)
const microfone = ref(false)
const erro = ref('')
const carregando = ref(false)
const url = ref('')

/** RNF007: antes de entrar, testa câmera/microfone e registra o resultado (evento `verificacao_dispositivo`). */
async function verificarDispositivos(): Promise<void> {
  erro.value = ''
  carregando.value = true
  try {
    const fluxo = await navigator.mediaDevices.getUserMedia({ audio: true, video: true })
    fluxo.getTracks().forEach((t) => t.stop()) // só testa; libera o dispositivo
    const dispositivos = await navigator.mediaDevices.enumerateDevices()
    camera.value = dispositivos.some((d) => d.kind === 'videoinput')
    microfone.value = dispositivos.some((d) => d.kind === 'audioinput')
  } catch {
    camera.value = false
    microfone.value = false
    erro.value =
      'Não foi possível acessar a câmera ou o microfone. Libere a permissão no navegador e tente de novo.'
  }
  try {
    await telemedicinaApi.evento(sala, 'verificacao_dispositivo', {
      camera: camera.value,
      microfone: microfone.value,
    })
  } catch (e) {
    erro.value ||= mensagemDe(e)
  } finally {
    carregando.value = false
  }
  if (camera.value && microfone.value) etapa.value = 'pronto'
}

async function entrar(): Promise<void> {
  erro.value = ''
  carregando.value = true
  try {
    const r = await telemedicinaApi.entrar(sala)
    const dominio = r.dominio || import.meta.env.VITE_JITSI_DOMAIN
    url.value = `https://${dominio}/${encodeURIComponent(r.sala)}?jwt=${encodeURIComponent(r.jwt)}#config.prejoinPageEnabled=false`
    etapa.value = 'sala'
    void telemedicinaApi.evento(sala, 'entrou').catch(() => undefined)
  } catch (e) {
    erro.value = mensagemDe(e) // 403: fora da janela, não participante, sala encerrada
  } finally {
    carregando.value = false
  }
}

async function sair(): Promise<void> {
  void telemedicinaApi.evento(sala, 'saiu').catch(() => undefined)
  url.value = ''
  etapa.value = 'encerrada'
}

async function encerrar(): Promise<void> {
  try {
    await telemedicinaApi.encerrar(sala)
    await sair()
  } catch (e) {
    erro.value = mensagemDe(e)
  }
}

const ehProfissional = computed(() => auth.perfil === 'profissional_saude')
onBeforeUnmount(() => {
  url.value = ''
})
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Teleconsulta</h1>
  <AlertaMsg v-if="erro" tom="erro" class="mb-4">{{ erro }}</AlertaMsg>

  <section
    v-if="etapa === 'verificar'"
    aria-labelledby="t-verif"
    class="flex max-w-xl flex-col gap-4"
  >
    <h2 id="t-verif" class="text-xl font-semibold">Passo 1: testar câmera e microfone</h2>
    <p>Vamos conferir se o seu aparelho está pronto. O navegador vai pedir permissão.</p>
    <BaseButton :carregando="carregando" @click="verificarDispositivos">Testar agora</BaseButton>
  </section>

  <section
    v-else-if="etapa === 'pronto'"
    aria-labelledby="t-pronto"
    class="flex max-w-xl flex-col gap-4"
  >
    <h2 id="t-pronto" class="text-xl font-semibold">Passo 2: entrar na sala</h2>
    <AlertaMsg tom="sucesso">Câmera e microfone funcionando.</AlertaMsg>
    <BaseButton :carregando="carregando" @click="entrar">Entrar na teleconsulta</BaseButton>
  </section>

  <section
    v-else-if="etapa === 'sala'"
    aria-label="Sala de teleconsulta"
    class="flex flex-col gap-3"
  >
    <iframe
      :src="url"
      title="Sala de teleconsulta"
      allow="camera; microphone; display-capture; fullscreen; autoplay"
      referrerpolicy="no-referrer"
      class="h-[70vh] w-full rounded-md border-2 border-border-strong"
    />
    <div class="flex flex-wrap gap-3">
      <BaseButton variante="secundario" @click="sair">Sair da sala</BaseButton>
      <BaseButton v-if="ehProfissional" variante="perigo" @click="encerrar"
        >Encerrar consulta para todos</BaseButton
      >
    </div>
  </section>

  <section v-else>
    <AlertaMsg tom="info">Você saiu da teleconsulta.</AlertaMsg>
  </section>
</template>
