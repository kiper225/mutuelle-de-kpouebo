<script setup>
import { reactive, ref, onBeforeUnmount } from 'vue'
import { api } from '../api'
import { MARITAL_STATUS } from '../utils'

const form = reactive({
  last_name: '', first_names: '', birth_date: '', birth_place: '', children_count: '',
  marital_status: '', profession: '', residence: '', mutuelle_role: 'Membre',
  phone: '', email: '', password: '', password_confirmation: '', accept_terms: false,
})
const photo = ref(null)
const preview = ref('')
const errors = ref({})
const error = ref('')
const success = ref('')
const sending = ref(false)

const err = (name) => errors.value[name]?.[0] || ''

function onPhoto(event) {
  const file = event.target.files[0] || null
  if (preview.value) URL.revokeObjectURL(preview.value)
  photo.value = file
  preview.value = file ? URL.createObjectURL(file) : ''
}

onBeforeUnmount(() => {
  if (preview.value) URL.revokeObjectURL(preview.value)
})

async function submit() {
  if (!photo.value) {
    errors.value = { photo: ['La photo est obligatoire.'] }
    error.value = 'Veuillez corriger les champs indiqués.'
    return
  }
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
    fd.append('photo', photo.value)
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
  <div class="register-view">
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

          <h3 style="margin: 8px 0 0">État civil (obligatoire)</h3>
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
              <input id="birth_date" v-model="form.birth_date" type="date" required />
              <span class="error">{{ err('birth_date') }}</span>
            </div>
            <div class="field">
              <label for="birth_place">Lieu de naissance</label>
              <input id="birth_place" v-model="form.birth_place" type="text" required />
              <span class="error">{{ err('birth_place') }}</span>
            </div>
            <div class="field">
              <label for="children_count">Nombre d'enfants</label>
              <input id="children_count" v-model="form.children_count" type="number" min="0" max="30" required />
              <span class="error">{{ err('children_count') }}</span>
            </div>
          </div>
          <div class="row" style="align-items: flex-end">
            <div class="field">
              <label for="photo">Photo d'identité (JPG, PNG ou WebP, 2 Mo maximum)</label>
              <input id="photo" type="file" accept="image/jpeg,image/png,image/webp" required @change="onPhoto" />
              <span class="error">{{ err('photo') }}</span>
            </div>
            <img v-if="preview" :src="preview" alt="Aperçu de la photo" style="width: 90px; height: 115px; object-fit: cover; border-radius: 6px; border: 1px solid var(--line)" />
          </div>

          <h3 style="margin: 16px 0 0">Informations personnelles</h3>
          <div class="row">
            <div class="field">
              <label for="marital_status">Statut matrimonial</label>
              <select id="marital_status" v-model="form.marital_status">
                <option value="">Non précisé</option>
                <option v-for="(label, key) in MARITAL_STATUS" :key="key" :value="key">{{ label }}</option>
              </select>
              <span class="error">{{ err('marital_status') }}</span>
            </div>
            <div class="field">
              <label for="profession">Profession</label>
              <input id="profession" v-model="form.profession" type="text" />
            </div>
          </div>
          <div class="row">
            <div class="field">
              <label for="residence">Lieu de résidence</label>
              <input id="residence" v-model="form.residence" type="text" placeholder="Kpouèbo, Abidjan, autre…" />
            </div>
            <div class="field">
              <label for="mutuelle_role">Poste au sein de la mutuelle</label>
              <input id="mutuelle_role" v-model="form.mutuelle_role" type="text" />
              <span class="muted">Laissez « Membre » si vous n'avez pas de poste. Le bureau vérifiera ce renseignement.</span>
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input id="email" v-model="form.email" type="email" required />
              <span class="error">{{ err('email') }}</span>
            </div>
          </div>

          <h3 style="margin: 16px 0 0">Votre compte</h3>
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
  </div>
</template>