<script setup lang="ts">
// O canvas não é lido por leitor de tela: a tabela logo abaixo é a versão acessível (comentário fora do <template>: comentário na raiz vira fragmento e quebra class/attrs)
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { BarController, BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js'

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip)

const props = defineProps<{ rotulos: string[]; valores: number[]; titulo: string }>()
const canvas = ref<HTMLCanvasElement | null>(null)
let grafico: Chart | null = null

function desenhar(): void {
  grafico?.destroy()
  if (!canvas.value) return
  const cor =
    getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#123524'
  grafico = new Chart(canvas.value, {
    type: 'bar',
    data: {
      labels: props.rotulos,
      datasets: [{ label: props.titulo, data: props.valores, backgroundColor: cor }],
    },
    options: { responsive: true, animation: false, plugins: { legend: { display: false } } },
  })
}
onMounted(desenhar)
watch(() => [props.rotulos, props.valores], desenhar)
onBeforeUnmount(() => grafico?.destroy())
</script>

<template>
  <canvas
    ref="canvas"
    role="img"
    :aria-label="`Gráfico de barras: ${titulo}. Os dados estão na tabela abaixo.`"
  />
</template>
