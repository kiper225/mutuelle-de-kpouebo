<script setup>
import { reactive, ref } from 'vue'
import { api } from '../api'

const form = reactive({
  last_name: '', first_names: '', birth_date: '', profession: '',
  phone: '', email: '', residence: '', password: '', password_confirmation: '', accept_terms: false,
})
const photo = ref(null)
const errors = ref({})
const error = ref('')
const success = ref('')
const sending = ref(false)

const err = (name) => errors.value[name]?.[0] || ''

async function submit() {
  sending.value = true
  errors.value = {}
  error.value = ''
  try {
    const fd = new FormData()
    for (const [k, v] of Object.entries(form)) {
      if (k === 'accept_terms') {
        if (v) fd.append(k, '1')
      } else if (v !== '') {
        fd.append(k, v)
      }
    }
    if (photo.value) fd.append('photo', photo.value)
    const res = await api.post('/register', fd)
    success.value = res.message
  } catch (e) {
    errors.value = e.errors
    error.value = Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="page-banner"><div class="container"><h1>Devenir membre de la mutuelle</h1></div></div>
  <section class="section alt">
    <div class="container narrow">
      <div v-if="success" class="card">
        <p class="alert ok">{{ success }}</p>
        <p>Vous pourrez vous connecter dès que votre adhésion aura été validée.</p>
        <RouterLink to="/" class="btn green">Retour à l'accueil</RouterLink>
      </div>

      <form v-else class="card form" @submit.prevent="submit">
        <h2 style="color: var(--green)">Formulaire d'adhésion</h2>
        <p v-if="error" class="alert err">{{ error }}</p>

        <div class="row">
          <div class="field">
            <label for="last_name">Nom</label>
            <input id="last_name" v-model="form.last_name" type="text" required />
            <span class="error">{{ err('last_name') }}</span>
          </div>
          <div class="field">
            <label for="first_names">Prénoms</label>
            <input id="first_names" v-model="form.first_names" type="text" required />
            <span class="error">{{ err('first_names') }}</span>
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label for="birth_date">Date de naissance</label>
            <input id="birth_date" v-model="form.birth_date" type="date" />
            <span class="error">{{ err('birth_date') }}</span>
          </div>
          <div class="field">
            <label for="profession">Profession</label>
            <input id="profession" v-model="form.profession" type="text" />
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label for="phone">Téléphone (Mobile Money)</label>
            <input id="phone" v-model="form.phone" type="tel" placeholder="+225…" required />
            <span class="error">{{ err('phone') }}</span>
          </div>
          <div class="field">
            <label for="email">Email (facultatif)</label>
            <input id="email" v-model="form.email" type="email" />
            <span class="error">{{ err('email') }}</span>
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label for="residence">Lieu de résidence</label>
            <input id="residence" v-model="form.residence" type="text" placeholder="Kpouèbo, Abidjan, autre…" />
          </div>
          <div class="field">
            <label for="photo">Photo de profil (facultatif)</label>
            <input id="photo" type="file" accept="image/*" @change="photo = $event.target.files[0]" />
            <span class="error">{{ err('photo') }}</span>
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label for="password">Mot de passe (8 caractères minimum)</label>
            <input id="password" v-model="form.password" type="password" autocomplete="new-password" required />
            <span class="error">{{ err('password') }}</span>
          </div>
          <div class="field">
            <label for="password2">Confirmer le mot de passe</label>
            <input id="password2" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
          </div>
        </div>
        <label class="check">
          <input v-model="form.accept_terms" type="checkbox" />
          J'accepte les statuts et le règlement intérieur de la mutuelle.
        </label>
        <span class="error">{{ err('accept_terms') }}</span>

        <button class="btn orange" type="submit" :disabled="sending" style="align-self: flex-start">
          {{ sending ? 'Envoi…' : "Envoyer ma demande d'adhésion" }}
        </button>
        <p class="muted">Déjà membre ? <RouterLink to="/connexion">Se connecter</RouterLink></p>
      </form>
    </div>
  </section>
</template>
