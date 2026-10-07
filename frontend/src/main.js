import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { initAuth } from './auth'
import './styles.css'

// On attend de connaître l'utilisateur connecté avant d'afficher l'application.
initAuth().finally(() => {
  createApp(App).use(router).mount('#app')
})
