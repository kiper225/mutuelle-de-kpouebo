<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'

const route = useRoute()
const token = String(route.query.token || '')
const email = String(route.query.email || '')

const password = ref('')
const confirmation = ref('')
const errors = ref({})
const error = ref('')
const done = ref('')
const sending = ref(false)

const err = (name) => errors.value[name]?.[0] || ''

async function submit() {
  sending.value = true
  errors.value = {}
  error.value = ''
  try {
    const res = await api.post('/reset-password', {
      token,
      email,
      password: password.value,
      password_confirmation: confirmation.value,
    })
    done.value = res.message
  } catch (e) {
    errors.value = e.errors
    error.value = e.field('email') || (Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message)
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Nouveau mot de passe</h1></div></div>
    <section class="section alt">
    <div class="container" style="max-width: 480px">
      <div v-if="done" class="card">
        <p class="alert ok">{{ done }}</p>
        <RouterLink to="/connexion" class="btn green">Se connecter</RouterLink>
      </div>

      <div v-else-if="!token || !email" class="card">
        <p class="alert err">Ce lien est incomplet. Refaites une demande de réinitialisation.</p>
        <RouterLink to="/mot-de-passe-oublie" class="btn green">Demander un nouveau lien</RouterLink>
      </div>

      <form v-else class="card form" @submit.prevent="submit">
        <p v-if="error" class="alert err">
          {{ error }}
          <RouterLink to="/mot-de-passe-oublie">Demander un nouveau lien</RouterLink>
        </p>
        <div class="field">
          <label for="password">Nouveau mot de passe (8 caractères minimum)</label>
          <input id="password" v-model="password" type="password" autocomplete="new-password" required />
          <span class="error">{{ err('password') }}</span>
        </div>
        <div class="field">
          <label for="password2">Confirmer le mot de passe</label>
          <input id="password2" v-model="confirmation" type="password" autocomplete="new-password" required />
        </div>
        <button class="btn green" type="submit" :disabled="sending">{{ sending ? 'Enregistrement…' : 'Enregistrer' }}</button>
      </form>
    </div>
    </section>
  </div>
</template>