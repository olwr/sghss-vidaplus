<script setup lang="ts">
import { computed, useId } from 'vue'
import { useField } from 'vee-validate'

const props = withDefaults(
  defineProps<{
    name: string
    label: string
    type?: string
    dica?: string
    autocomplete?: string
    inputmode?: 'text' | 'numeric' | 'email' | 'tel'
    maxlength?: number
    obrigatorio?: boolean
  }>(),
  { type: 'text', obrigatorio: false },
)

const { value, errorMessage, handleBlur } = useField<string>(() => props.name)
const id = useId()
const idErro = computed(() => `${id}-erro`)
const idDica = computed(() => `${id}-dica`)
const descritoPor = computed(
  () =>
    [errorMessage.value ? idErro.value : '', props.dica ? idDica.value : '']
      .filter(Boolean)
      .join(' ') || undefined,
)
</script>

<template>
  <div class="flex flex-col gap-1">
    <label :for="id" class="font-medium text-text">
      {{ label }}<span v-if="obrigatorio" aria-hidden="true" class="text-danger"> *</span>
    </label>
    <p v-if="dica" :id="idDica" class="text-sm text-text-secondary">{{ dica }}</p>
    <input
      :id="id"
      v-model="value"
      :name="name"
      :type="type"
      :autocomplete="autocomplete"
      :inputmode="inputmode"
      :maxlength="maxlength"
      :required="obrigatorio"
      :aria-invalid="errorMessage ? 'true' : undefined"
      :aria-describedby="descritoPor"
      class="min-h-11 rounded-md border-2 border-border-strong bg-surface px-3 py-2 text-text"
      @blur="handleBlur"
    />
    <p
      v-if="errorMessage"
      :id="idErro"
      class="flex items-center gap-1 text-sm font-medium text-danger"
    >
      <span aria-hidden="true">⚠</span> {{ errorMessage }}
    </p>
  </div>
</template>
