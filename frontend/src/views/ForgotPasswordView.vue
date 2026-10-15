<script setup>
import { ref } from 'vue'
import { api } from '../api'
import { site } from '../content'

const login = ref('')
const error = ref('')
const sent = ref('')
const sending = ref(false)

async function submit() {
  sending.value = true
  error.value = ''
  sent.value = ''
  try {
    const res = await api.post('/forgot-password', { login: login.value.trim() })
    sent.value = res.message
  } catch (e) {
    error.value = e.field('login') || e.message
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Mot de passe oublié</h1></div></div>
    <section class="section alt">
      <div class="container" style="max-width: 480px">
        <div v-if="sent" class="card">
          <p class="alert ok">{{ sent }}</p>
          <p>Pensez à vérifier vos courriers indésirables. Le lien est valable 60 minutes.</p>
          <p class="muted">
            Vous n'avez pas d'adresse email enregistrée ? Contactez le bureau de la mutuelle ({{ site.contact.phone }}) :
            il pourra vous donner un mot de passe provisoire.
          </p>
          <RouterLink to="/connexion" class="btn green">Retour à la connexion</RouterLink>
        </div>

        <form v-else class="card form" @submit.prevent="submit">
          <p class="muted" style="margin: 0">
            Saisissez votre adresse email ou votre numéro de téléphone. Si votre compte a une adresse email, vous
            recevrez un lien pour choisir un nouveau mot de passe.
          </p>
          <p v-if="error" class="alert err">{{ error }}</p>
          <div class="">
            <label for="login">Email ou téléphone</label>
            <input id="login" v-model="login" type="text" autocomplete="username" required />
          </div>
          <button class="btn green" type="submit" :disabled="sending">{{ sending ? 'Envoi…' : 'Envoyer le lien' }}</button>
          <p class="muted">
            Si vous n'avez pas d'email, ni de numéro de téléphone enregistré ? <br/> 
            Veuillez contactez le bureau ({{ site.contact.phone }}).<br/>
            <RouterLink to="/connexion">&larr; Retour à la connexion</RouterLink>
          </p>
        </form>
      </div>
    </section>
  </div>
</template>