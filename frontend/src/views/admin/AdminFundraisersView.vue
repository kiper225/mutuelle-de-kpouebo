<script setup>
import { reactive, ref, watch, onMounted } from 'vue'
import { api } from '../../api'
import { COTISATION_STATUS, formatMoney, formatDate } from '../../utils'
import PhotoField from '../../components/PhotoField.vue'
import Pager from '../../components/Pager.vue'

const emptyForm = () => ({
  id: null, title: '', summary: '', description: '', goal_amount: '', suggested_amount: '',
  starts_on: '', ends_on: '', is_active: true, image: null, image_url: '', remove_image: false,
})

const items = ref([])
const form = reactive(emptyForm())
const fileKey = ref(0)
const errors = ref({})
const error = ref('')
const success = ref('')
const saving = ref(false)

// Contributions d'une collecte
const openId = ref(null)
const contribs = ref([])
const cpage = ref(1)
const clast = ref(1)
const memberQuery = ref('')
const memberResults = ref([])
const chosen = ref(null)
const cashAmount = ref('')
const recording = ref(false)
let searchTimer = null

const err = (name) => errors.value[name]?.[0] || ''
const percent = (f) => (f.goal_amount ? Math.min(100, Math.round(((f.raised_amount ?? 0) / f.goal_amount) * 100)) : null)
const day = (value) => (value ? String(value).slice(0, 10) : '')

async function load() {
  try {
    items.value = await api.get('/admin/fundraisers')
  } catch (e) {
    error.value = e.message
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
    goal_amount: item.goal_amount ?? '',
    suggested_amount: item.suggested_amount ?? '',
    starts_on: day(item.starts_on),
    ends_on: day(item.ends_on),
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
    for (const key of ['title', 'summary', 'description', 'goal_amount', 'suggested_amount', 'starts_on', 'ends_on']) {
      fd.append(key, form[key]) // une valeur vide efface le champ
    }
    fd.append('is_active', form.is_active ? '1' : '0')
    if (form.image) fd.append('image', form.image)
    if (form.remove_image && !form.image) fd.append('remove_image', '1')
    if (form.id) fd.append('_method', 'PUT') // envoi multipart : Laravel lit _method

    await api.post(form.id ? `/admin/fundraisers/${form.id}` : '/admin/fundraisers', fd)
    success.value = form.id ? 'Collecte modifiée.' : 'Collecte créée.'
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
  if (!confirm(`Supprimer la collecte « ${item.title} » ?`)) return
  error.value = ''
  try {
    await api.del(`/admin/fundraisers/${item.id}`)
    if (form.id === item.id) reset()
    if (openId.value === item.id) openId.value = null
    await load()
  } catch (e) {
    error.value = e.message // ex. : la collecte a déjà reçu des contributions
  }
}

async function loadContribs(p = 1) {
  if (!openId.value) return
  try {
    const res = await api.get(`/admin/fundraisers/${openId.value}/contributions`, { page: p })
    contribs.value = res.data
    cpage.value = res.current_page
    clast.value = res.last_page
  } catch (e) {
    error.value = e.message
  }
}

async function toggleContribs(item) {
  if (openId.value === item.id) {
    openId.value = null
    return
  }
  openId.value = item.id
  chosen.value = null
  cashAmount.value = item.suggested_amount ?? ''
  memberQuery.value = ''
  memberResults.value = []
  await loadContribs(1)
}

watch(memberQuery, (value) => {
  clearTimeout(searchTimer)
  const term = value.trim()
  if (term.length < 2) {
    memberResults.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    try {
      const res = await api.get('/admin/members', { status: 'active', q: term, page: 1 })
      memberResults.value = res.data
    } catch (e) {
      error.value = e.message
    }
  }, 300)
})

function chooseMember(member) {
  chosen.value = { id: member.id, name: member.name }
  memberQuery.value = ''
  memberResults.value = []
}

async function recordCash() {
  if (!chosen.value || !cashAmount.value) return
  recording.value = true
  error.value = ''
  success.value = ''
  try {
    await api.post(`/admin/fundraisers/${openId.value}/contributions`, {
      user_id: chosen.value.id,
      amount: Number(cashAmount.value),
    })
    success.value = `Contribution de ${chosen.value.name} enregistrée.`
    chosen.value = null
    await Promise.all([loadContribs(1), load()])
  } catch (e) {
    error.value = e.field('amount') || e.field('user_id') || e.message
  } finally {
    recording.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="section">
    <div class="container">
      <p v-if="error" class="alert err">{{ error }}</p>
      <p v-if="success" class="alert ok">{{ success }}</p>

      <form class="card form" style="margin-bottom: 24px" @submit.prevent="save">
        <h2 style="color: var(--green)">{{ form.id ? 'Modifier la collecte' : 'Nouvelle collecte' }}</h2>
        <p class="muted" style="margin: 0">
          Une collecte sert à lever des fonds en plus des cotisations mensuelles, pour un chantier ou une activité de la mutuelle.
        </p>

        <div class="field">
          <label for="f-title">Titre</label>
          <input id="f-title" v-model="form.title" type="text" required maxlength="150" />
          <span class="error">{{ err('title') }}</span>
        </div>
        <div class="field">
          <label for="f-summary">Résumé en quelques mots (300 caractères maximum)</label>
          <input id="f-summary" v-model="form.summary" type="text" maxlength="300" />
          <span class="error">{{ err('summary') }}</span>
        </div>
        <div class="field">
          <label for="f-desc">Explication de l'activité (2 000 caractères maximum)</label>
          <textarea id="f-desc" v-model="form.description" rows="5" maxlength="2000"></textarea>
          <span class="error">{{ err('description') }}</span>
        </div>

        <PhotoField
          :key="fileKey"
          v-model="form.image"
          label="Image (facultatif)"
          :aspect="16 / 9"
          :output-width="1280"
          :current-url="form.id && !form.remove_image ? form.image_url : ''"
          :error="err('image')"
          hint="Une photo du chantier ou de l'activité. Vous pourrez la recadrer avant l'envoi."
        />
        <label v-if="form.id && form.image_url && !form.image" class="check">
          <input v-model="form.remove_image" type="checkbox" />
          Supprimer l'image actuelle
        </label>

        <div class="row">
          <div class="field">
            <label for="f-goal">Objectif à atteindre (FCFA, facultatif)</label>
            <input id="f-goal" v-model="form.goal_amount" type="number" min="1" />
            <span class="error">{{ err('goal_amount') }}</span>
          </div>
          <div class="field">
            <label for="f-suggested">Montant conseillé par membre (FCFA, facultatif)</label>
            <input id="f-suggested" v-model="form.suggested_amount" type="number" min="1" />
            <span class="error">{{ err('suggested_amount') }}</span>
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label for="f-start">Début (facultatif)</label>
            <input id="f-start" v-model="form.starts_on" type="date" />
            <span class="error">{{ err('starts_on') }}</span>
          </div>
          <div class="field">
            <label for="f-end">Fin (facultatif)</label>
            <input id="f-end" v-model="form.ends_on" type="date" />
            <span class="error">{{ err('ends_on') }}</span>
          </div>
        </div>

        <label class="check">
          <input v-model="form.is_active" type="checkbox" />
          Afficher sur le site
        </label>

        <div class="actions">
          <button class="btn orange" type="submit" :disabled="saving">
            {{ saving ? 'Enregistrement…' : form.id ? 'Enregistrer' : 'Créer la collecte' }}
          </button>
          <button v-if="form.id" class="btn outline-green" type="button" @click="reset">Annuler la modification</button>
        </div>
      </form>

      <div class="card">
        <h2 style="color: var(--green)">Collectes ({{ items.length }})</h2>
        <p v-if="!items.length" class="muted">Aucune collecte. Créez la première avec le formulaire ci-dessus.</p>

        <ul v-else class="manage-list">
          <li v-for="f in items" :key="f.id" class="manage-item" style="align-items: flex-start">
            <img v-if="f.image_url" :src="f.image_url" alt="" class="manage-thumb" />
            <span v-else class="manage-thumb"></span>

            <div class="manage-info">
              <b>{{ f.title }}</b>
              <span class="muted">{{ f.summary }}</span>
              <span v-if="!f.is_active" class="badge warn">Masquée</span>
              <span v-else-if="!f.is_open" class="badge warn">Hors dates</span>
              <div style="margin-top: 8px; max-width: 420px">
                <div v-if="f.goal_amount" class="progress"><span :style="{ width: percent(f) + '%' }"></span></div>
                <span class="muted">
                  {{ formatMoney(f.raised_amount ?? 0) }} collectés
                  <template v-if="f.goal_amount"> sur {{ formatMoney(f.goal_amount) }} ({{ percent(f) }} %)</template>
                  · {{ f.contributions_count }} contribution(s)
                </span>
              </div>
            </div>

            <div class="actions">
              <button class="btn outline-green small" type="button" @click="toggleContribs(f)">
                {{ openId === f.id ? 'Masquer les contributions' : 'Contributions' }}
              </button>
              <button class="btn outline-green small" type="button" @click="edit(f)">Modifier</button>
              <button class="btn danger small" type="button" @click="remove(f)">Supprimer</button>
            </div>

            <!-- Contributions de cette collecte -->
            <div v-if="openId === f.id" style="flex: 1 1 100%; border-top: 1px dashed var(--line); padding-top: 14px">
              <h3 style="font-size: 16px">Enregistrer une contribution en espèces</h3>
              <div class="row" style="align-items: flex-end">
                <div class="field">
                  <label for="c-member">Membre</label>
                  <input v-if="!chosen" id="c-member" v-model="memberQuery" type="text" placeholder="Rechercher par nom (2 lettres)" autocomplete="off" />
                  <div v-else class="gm-chips">
                    <span class="gm-chip">{{ chosen.name }}<button type="button" aria-label="Changer de membre" @click="chosen = null">×</button></span>
                  </div>
                </div>
                <div class="field" style="flex: 0 1 180px">
                  <label for="c-amount">Montant (FCFA)</label>
                  <input id="c-amount" v-model="cashAmount" type="number" min="1" />
                </div>
                <button class="btn green small" type="button" :disabled="!chosen || !cashAmount || recording" @click="recordCash">Enregistrer</button>
              </div>
              <div v-if="memberResults.length && !chosen" class="gm-results" style="margin-top: 8px">
                <button v-for="m in memberResults" :key="m.id" type="button" class="gm-result" @click="chooseMember(m)">
                  <span>{{ m.name }}</span><span class="muted">{{ m.phone }}</span>
                </button>
              </div>

              <h3 style="font-size: 16px; margin-top: 18px">Historique</h3>
              <p v-if="!contribs.length" class="muted">Aucune contribution pour l'instant.</p>
              <div v-else class="table-wrap">
                <table>
                  <thead><tr><th>Date</th><th>Membre</th><th>Montant</th><th>Mode</th><th>Statut</th></tr></thead>
                  <tbody>
                    <tr v-for="c in contribs" :key="c.id">
                      <td>{{ formatDate(c.created_at) }}</td>
                      <td>{{ c.user?.name }}</td>
                      <td>{{ formatMoney(c.amount) }}</td>
                      <td>{{ c.recorded_by ? 'Espèces' : 'En ligne' }}</td>
                      <td>
                        <span class="badge" :class="{ warn: c.status === 'pending', bad: c.status === 'failed' }">
                          {{ COTISATION_STATUS[c.status] || c.status }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <Pager :page="cpage" :last="clast" @change="loadContribs" />
            </div>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>