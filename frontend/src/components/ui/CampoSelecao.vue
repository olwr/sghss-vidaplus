<script setup lang="ts">
import { computed, useId } from 'vue'
import { useField } from 'vee-validate'

const props = defineProps<{
  name: string
  label: string
  opcoes: { valor: string; texto: string }[]
  placeholder?: string
  obrigatorio?: boolean
}>()

const { value, errorMessage, handleBlur } = useField<string>(() => props.name)
const id = useId()
const idErro = computed(() => `${id}-erro`)
</script>

<template>
  <div class="flex flex-col gap-1">
    <label :for="id" class="font-medium text-text">
      {{ label }}<span v-if="obrigatorio" aria-hidden="true" class="text-danger"> *</span>
    </label>
    <select
      :id="id"
      v-model="value"
      :name="name"
      :required="obrigatorio"
      :aria-invalid="errorMessage ? 'true' : undefined"
      :aria-describedby="errorMessage ? idErro : undefined"
      class="min-h-11 rounded-md border-2 border-border-strong bg-surface px-3 py-2 text-text"
      @blur="handleBlur"
    >
      <option value="">{{ placeholder ?? 'Selecione' }}</option>
      <option v-for="o in opcoes" :key="o.valor" :value="o.valor">{{ o.texto }}</option>
    </select>
    <p
      v-if="errorMessage"
      :id="idErro"
      class="flex items-center gap-1 text-sm font-medium text-danger"
    >
      <span aria-hidden="true">⚠</span> {{ errorMessage }}
    </p>
  </div>
</template>
