<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../../api'
import { MARITAL_STATUS, MEMBER_STATUS, formatDateShort } from '../../utils'
import AdminNav from '../../components/AdminNav.vue'

const route = useRoute()
const id = route.params.id

const member = ref(null)
const form = reactive({
  last_name: '', first_names: '', birth_date: '', birth_place: '', children_count: '',
  marital_status: '', profession: '', residence: '', mutuelle_role: '',
})
const errors = ref({})
const error = ref('')
const success = ref('')
const saving = ref(false)

const err = (name) => errors.value[name]?.[0] || ''

function fill(m) {
  Object.assign(form, {
    last_name: m.last_name || '',
    first_names: m.first_names || '',
    birth_date: m.birth_date ? String(m.birth_date).slice(0, 10) : '',
    birth_place: m.birth_place || '',
    children_count: m.children_count ?? '',
    marital_status: m.marital_status || '',
    profession: m.profession || '',
    residence: m.residence || '',
    mutuelle_role: m.mutuelle_role || '',
  })
}

async function load() {
  try {
    member.value = await api.get(`/admin/members/${id}`)
    fill(member.value)
  } catch (e) {
    error.value = e.message
  }
}

async function save() {
  saving.value = true
  errors.value = {}
  error.value = ''
  success.value = ''
  try {
    member.value = await api.put(`/admin/members/${id}`, {
      ...form,
      children_count: form.children_count === '' ? undefined : Number(form.children_count),
    })
    fill(member.value)
    success.value = 'Dossier enregistré.'
  } catch (e) {
    errors.value = e.errors
    error.value = Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Dossier du membre</h1></div></div>
    <section class="section alt">
      <div class="container">
        <AdminNav />
        <p v-if="error" class="alert err">{{ error }}</p>
        <p v-if="success" class="alert ok">{{ success }}</p>

        <template v-if="member">
          <div class="card" style="margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 24px; align-items: center">
            <img v-if="member.photo_url" :src="member.photo_url" alt="Photo du membre" style="width: 90px; height: 115px; object-fit: cover; border-radius: 6px" />
            <div>
              <h2 style="color: var(--green); margin: 0">{{ member.name }}</h2>
              <p class="muted" style="margin: 4px 0">
                Statut : {{ MEMBER_STATUS[member.status] || member.status }}
                · N° {{ member.member_number || 'non attribué (validez d\'abord l\'adhésion)' }}
                · Adhésion : {{ formatDateShort(member.joined_at) || '—' }}
              </p>
              <p class="muted" style="margin: 0">Téléphone : {{ member.phone }}<span v-if="member.email"> · {{ member.email }}</span></p>
            </div>
          </div>

          <div class="card" style="margin-bottom: 24px">
            <h2 style="color: var(--green)">Documents à imprimer</h2>
            <p class="muted">Validez l'adhésion et vérifiez le poste avant d'imprimer : le numéro de membre n'existe qu'après validation.</p>
            <div class="actions">
              <RouterLink :to="`/imprimer/fiche/${member.id}`" target="_blank" class="btn orange small">Fiche d'adhésion</RouterLink>
              <RouterLink :to="`/imprimer/badge/${member.id}`" target="_blank" class="btn orange small">Carte de membre</RouterLink>
              <RouterLink :to="`/imprimer/carton/${member.id}`" target="_blank" class="btn orange small">Carton annuel</RouterLink>
            </div>
          </div>

          <form class="card form" @submit.prevent="save">
            <h2 style="color: var(--green)">Modifier le dossier</h2>
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
                <label for="birth_place">Lieu de naissance</label>
                <input id="birth_place" v-model="form.birth_place" type="text" />
                <span class="error">{{ err('birth_place') }}</span>
              </div>
              <div class="field">
                <label for="children_count">Nombre d'enfants</label>
                <input id="children_count" v-model="form.children_count" type="number" min="0" max="30" />
                <span class="error">{{ err('children_count') }}</span>
              </div>
            </div>
            <div class="row">
              <div class="field">
                <label for="marital_status">Statut matrimonial</label>
                <select id="marital_status" v-model="form.marital_status">
                  <option value="">Non précisé</option>
                  <option v-for="(label, key) in MARITAL_STATUS" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div class="field">
                <label for="profession">Profession</label>
                <input id="profession" v-model="form.profession" type="text" />
              </div>
            </div>
            <div class="row">
              <div class="field">
                <label for="residence">Lieu de résidence</label>
                <input id="residence" v-model="form.residence" type="text" />
              </div>
              <div class="field">
                <label for="mutuelle_role">Poste au sein de la mutuelle</label>
                <input id="mutuelle_role" v-model="form.mutuelle_role" type="text" />
              </div>
            </div>
            <button class="btn green" type="submit" :disabled="saving" style="align-self: flex-start">Enregistrer</button>
          </form>
        </template>
      </div>
    </section>
  </div>
</template>