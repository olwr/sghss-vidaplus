<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { agendamentosApi, authApi, profissionaisApi } from '@/api/servicos'
import { mensagemDe } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { hojeIso, hora } from '@/lib/formatar'
import type { Horario, Profissional, UnidadeResumo } from '@/types/api'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertaMsg from '@/components/ui/AlertaMsg.vue'

/** Fluxo curto (poucas ações por tela): unidade → profissional → dia → horário → confirmar. */
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const ehPaciente = computed(() => auth.perfil === 'paciente')
const pacienteAlvo = computed(() =>
  typeof route.query.paciente === 'string' ? route.query.paciente : '',
)

const unidades = ref<UnidadeResumo[]>([])
const profissionais = ref<Profissional[]>([])
const horarios = ref<Horario[]>([])
const unidadeId = ref('')
const profissionalId = ref('')
const dia = ref(hojeIso())
const escolhido = ref('')
const modalidade = ref<'presencial' | 'telemedicina'>('presencial')
const motivo = ref('')
const erro = ref('')
const aviso = ref('')
const buscando = ref(false)
const enviando = ref(false)

// UMA chave por tentativa: reenvio (clique duplo, rede instável) devolve o MESMO agendamento (CT023).
let chave = crypto.randomUUID()
watch([unidadeId, profissionalId, dia, escolhido, modalidade, motivo], () => {
  chave = crypto.randomUUID()
})

onMounted(async () => {
  try {
    unidades.value = await authApi.unidades()
  } catch (e) {
    erro.value = mensagemDe(e)
  }
})

watch(unidadeId, async (u) => {
  profissionalId.value = ''
  horarios.value = []
  profissionais.value = []
  if (!u) return
  try {
    profissionais.value = (
      await profissionaisApi.listar({ unidade: u, tipo: 'medico', per_page: 100 })
    ).data
  } catch (e) {
    erro.value = mensagemDe(e)
  }
})

async function verHorarios(): Promise<void> {
  erro.value = ''
  escolhido.value = ''
  buscando.value = true
  try {
    horarios.value = await agendamentosApi.disponibilidade(
      profissionalId.value,
      dia.value,
      unidadeId.value,
    )
    aviso.value = horarios.value.length
      ? `${horarios.value.length} horário(s) livre(s).`
      : 'Não há horários livres neste dia.'
  } catch (e) {
    erro.value = mensagemDe(e)
  } finally {
    buscando.value = false
  }
}

async function confirmar(): Promise<void> {
  erro.value = ''
  enviando.value = true
  try {
    const paciente = ehPaciente.value ? undefined : pacienteAlvo.value
    const ag = await agendamentosApi.criar(
      {
        tipo: 'consulta',
        modalidade: modalidade.value,
        paciente_id: paciente,
        profissional_id: profissionalId.value,
        unidade_id: unidadeId.value,
        inicio_em: escolhido.value,
        motivo: motivo.value || undefined,
      },
      chave,
    )
    await router.push(
      ehPaciente.value
        ? { name: 'paciente-agendamentos', query: { novo: ag.id } }
        : { name: 'recepcao-agenda' },
    )
  } catch (e) {
    erro.value = mensagemDe(e) // 409 "horário ocupado", 422 etc., já em português
    if ((e as { status?: number }).status === 409) await verHorarios()
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold text-primary">Agendar consulta</h1>

  <AlertaMsg v-if="!ehPaciente && !pacienteAlvo" tom="aviso">
    Escolha o paciente primeiro em
    <RouterLink to="/recepcao/pacientes" class="underline">Pacientes</RouterLink>.
  </AlertaMsg>

  <template v-else>
    <AlertaMsg v-if="erro" tom="erro" class="mb-4">{{ erro }}</AlertaMsg>

    <form class="flex max-w-xl flex-col gap-5" @submit.prevent="confirmar">
      <div class="flex flex-col gap-1">
        <label for="unidade" class="font-medium">1. Unidade</label>
        <select
          id="unidade"
          v-model="unidadeId"
          required
          class="min-h-11 rounded-md border-2 border-border-strong bg-surface px-3"
        >
          <option value="">Selecione</option>
          <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option>
        </select>
      </div>

      <div class="flex flex-col gap-1">
        <label for="profissional" class="font-medium">2. Profissional</label>
        <select
          id="profissional"
          v-model="profissionalId"
          required
          :disabled="!unidadeId"
          class="min-h-11 rounded-md border-2 border-border-strong bg-surface px-3 disabled:opacity-60"
        >
          <option value="">Selecione</option>
          <option v-for="p in profissionais" :key="p.id" :value="p.id">
            {{ p.nome }}{{ p.especialidade ? ` — ${p.especialidade}` : '' }}
          </option>
        </select>
      </div>

      <div class="flex flex-col gap-1">
        <label for="dia" class="font-medium">3. Dia</label>
        <input
          id="dia"
          v-model="dia"
          type="date"
          :min="hojeIso()"
          required
          class="min-h-11 rounded-md border-2 border-border-strong bg-surface px-3"
        />
        <BaseButton
          variante="secundario"
          :desabilitado="!profissionalId"
          :carregando="buscando"
          @click="verHorarios"
          >Ver horários livres</BaseButton
        >
      </div>

      <p v-if="aviso" role="status" class="text-text-secondary">{{ aviso }}</p>

      <fieldset v-if="horarios.length" class="flex flex-col gap-2">
        <legend class="mb-1 font-medium">4. Horário</legend>
        <div class="flex flex-wrap gap-2">
          <label v-for="h in horarios" :key="h.inicio" class="cursor-pointer">
            <input
              v-model="escolhido"
              type="radio"
              name="horario"
              :value="h.inicio"
              class="peer sr-only"
            />
            <span
              class="inline-flex min-h-11 min-w-20 items-center justify-center rounded-md border-2 border-border-strong px-3 font-semibold peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2"
            >
              {{ hora(h.inicio) }}
            </span>
          </label>
        </div>
      </fieldset>

      <fieldset v-if="escolhido" class="flex flex-col gap-3">
        <legend class="mb-1 font-medium">5. Como será a consulta?</legend>
        <label class="flex items-center gap-3"
          ><input
            v-model="modalidade"
            type="radio"
            value="presencial"
            class="size-6 accent-primary"
          />
          Presencial</label
        >
        <label class="flex items-center gap-3"
          ><input
            v-model="modalidade"
            type="radio"
            value="telemedicina"
            class="size-6 accent-primary"
          />
          Online (teleconsulta)</label
        >
        <label for="motivo" class="font-medium">Motivo (opcional)</label>
        <input
          id="motivo"
          v-model="motivo"
          maxlength="255"
          class="min-h-11 rounded-md border-2 border-border-strong bg-surface px-3"
        />
        <BaseButton type="submit" :carregando="enviando">Confirmar agendamento</BaseButton>
      </fieldset>
    </form>
  </template>
</template>
