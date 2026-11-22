<script setup lang="ts">
import { computed, useId } from 'vue'
import { useField } from 'vee-validate'

const props = defineProps<{ name: string; label: string }>()
const { checked, errorMessage, handleChange } = useField<boolean>(() => props.name, undefined, {
  type: 'checkbox',
  checkedValue: true,
  uncheckedValue: false,
})
const id = useId()
const idErro = computed(() => `${id}-erro`)
</script>

<template>
  <div class="flex flex-col gap-1">
    <div class="flex items-start gap-3">
      <input
        :id="id"
        type="checkbox"
        :name="name"
        :checked="checked"
        :aria-invalid="errorMessage ? 'true' : undefined"
        :aria-describedby="errorMessage ? idErro : undefined"
        class="mt-1 size-6 shrink-0 accent-primary"
        @change="handleChange(($event.target as HTMLInputElement).checked)"
      />
      <label :for="id" class="text-text"
        ><slot>{{ label }}</slot></label
      >
    </div>
    <p
      v-if="errorMessage"
      :id="idErro"
      class="flex items-center gap-1 text-sm font-medium text-danger"
    >
      <span aria-hidden="true">⚠</span> {{ errorMessage }}
    </p>
  </div>
</template>
