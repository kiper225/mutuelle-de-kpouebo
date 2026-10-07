<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { login } from '../auth'

const route = useRoute()
const router = useRouter()
const loginValue = ref('')
const password = ref('')
const error = ref('')
const sending = ref(false)

async function submit() {
  sending.value = true
  error.value = ''
  try {
    const user = await login(loginValue.value.trim(), password.value)
    const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/') ? route.query.redirect : null
    router.push(redirect || (user.role === 'admin' ? '/admin' : '/espace'))
  } catch (e) {
    error.value = e.field('login') || e.message
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Connexion</h1></div></div>
    <section class="section alt">
      <div class="container" style="max-width: 480px">
        <form class="card form" @submit.prevent="submit">
          <p v-if="error" class="alert err">{{ error }}</p>
          <div class="field">
            <label for="login">Téléphone ou email</label>
            <input id="login" v-model="loginValue" type="text" autocomplete="username" required />
          </div>
          <div class="field">
            <label for="password">Mot de passe</label>
            <input id="password" v-model="password" type="password" autocomplete="current-password" required />
          </div>
          <button class="btn green" type="submit" :disabled="sending">{{ sending ? 'Connexion…' : 'Se connecter' }}</button>
          <p class="muted">Pas encore membre ? <RouterLink to="/inscription">Faire une demande d'adhésion</RouterLink></p>
        </form>
      </div>
    </section>
  </div>
</template>
