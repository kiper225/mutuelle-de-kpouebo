<script setup>
import { reactive, ref } from 'vue'
import { api } from '../api'
import { auth } from '../auth'
import { MARITAL_STATUS, GENDER, formatDateShort } from '../utils'
import PhotoField from '../components/PhotoField.vue'

const u = auth.user

const form = reactive({
  email: u.email || '',
  marital_status: u.marital_status || '',
  children_count: u.children_count ?? '',
  profession: u.profession || '',
  residence: u.residence || '',
})
const photo = ref(null)
const photoKey = ref(0)
const errors = ref({})
const error = ref('')
const success = ref('')
const saving = ref(false)

const pwd = reactive({ current_password: '', password: '', password_confirmation: '' })
const pwdErrors = ref({})
const pwdError = ref('')
const pwdSuccess = ref('')
const pwdSaving = ref(false)

const err = (name) => errors.value[name]?.[0] || ''
const pwdErr = (name) => pwdErrors.value[name]?.[0] || ''

async function save() {
  saving.value = true
  errors.value = {}
  error.value = ''
  success.value = ''
  try {
    const fd = new FormData()
    for (const [key, value] of Object.entries(form)) fd.append(key, value) // une valeur vide efface le champ
    if (photo.value) fd.append('photo', photo.value)

    const updated = await api.post('/me/profile', fd)
    Object.assign(auth.user, updated)
    photo.value = null
    photoKey.value++
    success.value = 'Profil enregistré.'
  } catch (e) {
    errors.value = e.errors
    error.value = Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message
  } finally {
    saving.value = false
  }
}

async function changePassword() {
  pwdSaving.value = true
  pwdErrors.value = {}
  pwdError.value = ''
  pwdSuccess.value = ''
  try {
    const res = await api.post('/me/password', pwd)
    pwdSuccess.value = res.message
    Object.assign(pwd, { current_password: '', password: '', password_confirmation: '' })
  } catch (e) {
    pwdErrors.value = e.errors
    pwdError.value = Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message
  } finally {
    pwdSaving.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Mon profil</h1></div></div>
    <section class="section alt">
      <div class="container narrow">
        <div class="card" style="margin-bottom: 24px">
          <h2 style="color: var(--green)">Mon identité</h2>
          <p class="muted">
            Ces informations figurent sur vos documents (fiche d'adhésion, carte de membre). Pour les corriger,
            contactez le bureau de la mutuelle.
          </p>
          <div class="table-wrap">
            <table>
              <tbody>
                <tr><th>Nom</th><td>{{ auth.user.last_name }}</td></tr>
                <tr><th>Prénoms</th><td>{{ auth.user.first_names }}</td></tr>
                <tr><th>Sexe</th><td>{{ GENDER[auth.user.gender] || '—' }}</td></tr>
                <tr><th>Naissance</th><td>{{ formatDateShort(auth.user.birth_date) }} à {{ auth.user.birth_place }}</td></tr>
                <tr><th>Poste</th><td>{{ auth.user.mutuelle_role || '—' }}</td></tr>
                <tr><th>N° de membre</th><td>{{ auth.user.member_number || '—' }}</td></tr>
                <tr><th>Téléphone</th><td>{{ auth.user.phone }}</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <form class="card form" style="margin-bottom: 24px" @submit.prevent="save">
          <h2 style="color: var(--green)">Modifier mes informations</h2>
          <p v-if="error" class="alert err">{{ error }}</p>
          <p v-if="success" class="alert ok">{{ success }}</p>

          <PhotoField
            :key="photoKey"
            v-model="photo"
            label="Ma photo"
            :aspect="3 / 4"
            :output-width="600"
            :current-url="auth.user.photo_url || ''"
            :error="err('photo')"
            hint="Choisissez une nouvelle photo pour la remplacer : vous pourrez la recadrer avant l'envoi."
          />

          <div class="row">
            <div class="field">
              <label for="email">Email</label>
              <input id="email" v-model="form.email" type="email" />
              <span class="error">{{ err('email') }}</span>
            </div>
            <div class="field">
              <label for="marital_status">Statut matrimonial</label>
              <select id="marital_status" v-model="form.marital_status">
                <option value="">Non précisé</option>
                <option v-for="(label, key) in MARITAL_STATUS" :key="key" :value="key">{{ label }}</option>
              </select>
              <span class="error">{{ err('marital_status') }}</span>
            </div>
          </div>
          <div class="row">
            <div class="field">
              <label for="children_count">Nombre d'enfants</label>
              <input id="children_count" v-model="form.children_count" type="number" min="0" max="30" required />
              <span class="error">{{ err('children_count') }}</span>
            </div>
            <div class="field">
              <label for="profession">Profession</label>
              <input id="profession" v-model="form.profession" type="text" />
              <span class="error">{{ err('profession') }}</span>
            </div>
          </div>
          <div class="fiel">
            <label for="residence">Lieu de résidence</label>
            <input id="residence" v-model="form.residence" type="text" />
            <span class="error">{{ err('residence') }}</span>
          </div>
          <button class="btn green" type="submit" :disabled="saving" style="align-self: flex-start">
            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </form>

        <form class="card form" @submit.prevent="changePassword">
          <h2 style="color: var(--green)">Changer mon mot de passe</h2>
          <p v-if="pwdError" class="alert err">{{ pwdError }}</p>
          <p v-if="pwdSuccess" class="alert ok">{{ pwdSuccess }}</p>
          <div class="fiel">
            <label for="current_password">Mot de passe actuel</label>
            <input id="current_password" v-model="pwd.current_password" type="password" autocomplete="current-password" required />
            <span class="error">{{ pwdErr('current_password') }}</span>
          </div>
          <div class="row">
            <div class="field">
              <label for="new_password">Nouveau mot de passe</label>
              <input id="new_password" v-model="pwd.password" type="password" autocomplete="new-password" required />
              <div>(8 caractères minimum)</div>
              <span class="error">{{ pwdErr('password') }}</span>
            </div>
            <div class="field">
              <label for="new_password2">Confirmer le nouveau mot de passe</label>
              <input id="new_password2" v-model="pwd.password_confirmation" type="password" autocomplete="new-password" required />
            </div>
          </div>
          <button class="btn orange" type="submit" :disabled="pwdSaving" style="align-self: flex-start">Changer le mot de passe</button>
        </form>
      </div>
    </section>
  </div>
</template>