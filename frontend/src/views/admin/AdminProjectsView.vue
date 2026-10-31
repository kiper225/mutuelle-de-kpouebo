<script setup>
import { reactive, ref, onMounted } from 'vue'
import { api } from '../../api'
import { PROJECT_STATUS, PROJECT_BADGE, formatMoney } from '../../utils'
import PhotoField from '../../components/PhotoField.vue'

const emptyForm = () => ({
  id: null, title: '', summary: '', description: '', status: 'en_cours', progress: '', location: '', budget: '',
  starts_on: '', ends_on: '', fundraiser_id: '', is_active: true, image: null, image_url: '', remove_image: false,
})

const items = ref([])
const fundraisers = ref([])
const form = reactive(emptyForm())
const fileKey = ref(0)
const errors = ref({})
const error = ref('')
const success = ref('')
const saving = ref(false)

const err = (name) => errors.value[name]?.[0] || ''
const day = (value) => (value ? String(value).slice(0, 10) : '')

async function load() {
  try {
    items.value = await api.get('/admin/projects')
  } catch (e) {
    error.value = e.message
  }
}

async function loadFundraisers() {
  try {
    fundraisers.value = await api.get('/admin/fundraisers')
  } catch {
    /* la liste des levées de fonds reste simplement vide */
  }
}

function reset() {
  Object.assign(form, emptyForm())
  fileKey.value++
  errors.value = {}
}

function edit(item) {
  Object.assign(form, {
    id: item.id,
    title: item.title,
    summary: item.summary || '',
    description: item.description || '',
    status: item.status,
    progress: item.progress ?? '',
    location: item.location || '',
    budget: item.budget ?? '',
    starts_on: day(item.starts_on),
    ends_on: day(item.ends_on),
    fundraiser_id: item.fundraiser_id ?? '',
    is_active: !!item.is_active,
    image: null,
    image_url: item.image_url || '',
    remove_image: false,
  })
  fileKey.value++
  success.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function save() {
  saving.value = true
  errors.value = {}
  error.value = ''
  success.value = ''
  try {
    const fd = new FormData()
    for (const key of ['title', 'summary', 'description', 'status', 'progress', 'location', 'budget', 'starts_on', 'ends_on', 'fundraiser_id']) {
      fd.append(key, form[key]) // une valeur vide efface le champ
    }
    fd.append('is_active', form.is_active ? '1' : '0')
    if (form.image) fd.append('image', form.image)
    if (form.remove_image && !form.image) fd.append('remove_image', '1')
    if (form.id) fd.append('_method', 'PUT') // envoi multipart : Laravel lit _method

    await api.post(form.id ? `/admin/projects/${form.id}` : '/admin/projects', fd)
    success.value = form.id ? 'Chantier modifié.' : 'Chantier ajouté.'
    reset()
    await load()
  } catch (e) {
    errors.value = e.errors
    error.value = Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!confirm(`Supprimer « ${item.title} » ?`)) return
  try {
    await api.del(`/admin/projects/${item.id}`)
    if (form.id === item.id) reset()
    await load()
  } catch (e) {
    error.value = e.message
  }
}

async function move(index, direction) {
  const target = index + direction
  if (target < 0 || target >= items.value.length) return
  const list = [...items.value]
  ;[list[index], list[target]] = [list[target], list[index]]
  items.value = list
  try {
    await api.post('/admin/projects/reorder', { ids: list.map((p) => p.id) })
  } catch (e) {
    error.value = e.message
    await load()
  }
}

onMounted(() => {
  load()
  loadFundraisers()
})
</script>

<template>
  <section class="section">
    <div class="container">
      <p v-if="error" class="alert err">{{ error }}</p>
      <p v-if="success" class="alert ok">{{ success }}</p>

      <form class="card form" style="margin-bottom: 24px" @submit.prevent="save">
        <h2 style="color: var(--green)">{{ form.id ? 'Modifier le chantier' : 'Nouveau chantier ou projet' }}</h2>

        <div class="row">
          <div class="field" style="flex: 2 1 320px">
            <label for="p-title">Titre</label>
            <input id="p-title" v-model="form.title" type="text" required maxlength="150" />
            <span class="error">{{ err('title') }}</span>
          </div>
          <div class="field">
            <label for="p-status">Statut</label>
            <select id="p-status" v-model="form.status">
              <option v-for="(label, key) in PROJECT_STATUS" :key="key" :value="key">{{ label }}</option>
            </select>
            <span class="error">{{ err('status') }}</span>
          </div>
        </div>

        <div class="field">
          <label for="p-summary">Résumé en quelques mots (300 caractères maximum)</label>
          <input id="p-summary" v-model="form.summary" type="text" maxlength="300" />
          <span class="error">{{ err('summary') }}</span>
        </div>
        <div class="field">
          <label for="p-desc">Explication détaillée (3 000 caractères maximum)</label>
          <textarea id="p-desc" v-model="form.description" rows="6" maxlength="3000"></textarea>
          <span class="error">{{ err('description') }}</span>
        </div>

        <PhotoField
          :key="fileKey"
          v-model="form.image"
          label="Image (facultative)"
          :aspect="16 / 9"
          :output-width="1280"
          :current-url="form.id && !form.remove_image ? form.image_url : ''"
          :error="err('image')"
          hint="Une photo du chantier. Vous pourrez la recadrer avant l'envoi."
        />
        <label v-if="form.id && form.image_url && !form.image" class="check">
          <input v-model="form.remove_image" type="checkbox" />
          Supprimer l'image actuelle
        </label>

        <div class="row">
          <div class="field">
            <label for="p-progress">Avancement (%, pour un chantier en cours)</label>
            <input id="p-progress" v-model="form.progress" type="number" min="0" max="100" />
            <span class="error">{{ err('progress') }}</span>
          </div>
          <div class="field">
            <label for="p-location">Lieu</label>
            <input id="p-location" v-model="form.location" type="text" maxlength="150" />
          </div>
          <div class="field">
            <label for="p-budget">Budget (FCFA)</label>
            <input id="p-budget" v-model="form.budget" type="number" min="1" />
            <span class="error">{{ err('budget') }}</span>
          </div>
        </div>

        <div class="row">
          <div class="field">
            <label for="p-start">Début</label>
            <input id="p-start" v-model="form.starts_on" type="date" />
            <span class="error">{{ err('starts_on') }}</span>
          </div>
          <div class="field">
            <label for="p-end">Fin</label>
            <input id="p-end" v-model="form.ends_on" type="date" />
            <span class="error">{{ err('ends_on') }}</span>
          </div>
          <div class="">
            <label for="p-fund">Levée de fonds liée</label>
            <select id="p-fund" v-model="form.fundraiser_id">
              <option value="">Aucune</option>
              <option v-for="f in fundraisers" :key="f.id" :value="f.id">{{ f.title }}</option>
            </select>
            <span class="error">{{ err('fundraiser_id') }}</span>
          </div>
        </div>

        <label class="check">
          <input v-model="form.is_active" type="checkbox" />
          Afficher sur le site
        </label>

        <div class="actions">
          <button class="btn orange" type="submit" :disabled="saving">
            {{ saving ? 'Enregistrement…' : form.id ? 'Enregistrer' : 'Ajouter' }}
          </button>
          <button v-if="form.id" class="btn outline-green" type="button" @click="reset">Annuler la modification</button>
        </div>
      </form>

      <div class="card">
        <h2 style="color: var(--green)">Chantiers et projets ({{ items.length }})</h2>
        <p v-if="!items.length" class="muted">Aucun chantier enregistré. Ajoutez le premier avec le formulaire ci-dessus.</p>

        <ul v-else class="manage-list">
          <li v-for="(p, i) in items" :key="p.id" class="manage-item">
            <img v-if="p.image_url" :src="p.image_url" alt="" class="manage-thumb" />
            <span v-else class="manage-thumb"></span>

            <div class="manage-info">
              <b>{{ p.title }}</b>
              <span class="muted">{{ p.summary }}</span>
              <span>
                <span class="badge" :class="PROJECT_BADGE[p.status]">{{ PROJECT_STATUS[p.status] }}</span>
                <span v-if="p.status === 'en_cours' && p.progress !== null" class="muted"> · {{ p.progress }} %</span>
                <span v-if="p.budget" class="muted"> · {{ formatMoney(p.budget) }}</span>
                <span v-if="!p.is_active" class="badge warn" style="margin-left: 6px">Masqué</span>
              </span>
            </div>

            <div class="actions">
              <button class="btn outline-green small" type="button" aria-label="Monter" :disabled="i === 0" @click="move(i, -1)">↑</button>
              <button class="btn outline-green small" type="button" aria-label="Descendre" :disabled="i === items.length - 1" @click="move(i, 1)">↓</button>
              <button class="btn outline-green small" type="button" @click="edit(p)">Modifier</button>
              <button class="btn danger small" type="button" @click="remove(p)">Supprimer</button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>