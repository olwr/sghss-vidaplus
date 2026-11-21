import { createApp } from 'vue'
import { createPinia } from 'pinia'

import './assets/main.css'
import App from './App.vue'
import router from './router'
import { aoReceber401 } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { useAcessibilidadeStore } from '@/stores/acessibilidade'
import { z } from 'zod'

// CSP sem 'unsafe-eval': o zod testa `new Function` para otimizar e o navegador REPORTA isso como violação,
// mesmo com o erro capturado. `jitless` pula o teste (validação continua correta, só sem a otimização).
z.config({ jitless: true })

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(router)

// aplica as preferências de acessibilidade ANTES de montar (evita "piscar" tema/tamanho)
useAcessibilidadeStore(pinia).aplicar()

// sessão expirada/revogada (401 em rota protegida) → limpa e volta ao login
aoReceber401(() => {
  useAuthStore(pinia).limpar()
  void router.push({ name: 'login', query: { expirada: '1' } })
})

app.mount('#app')
