<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { api } from '../../api'
import { auth } from '../../auth'
import { formatDate } from '../../utils'
import Pager from '../../components/Pager.vue'

const AUDIENCES = { all: 'Tous les membres actifs', overdue: 'Membres en retard de cotisation', selected: 'Membres choisis' }
const MAX = 2000

const audience = ref('all')
const body = ref('')
const counts = reactive({ all: null, overdue: null })
const selected = ref([]) // [{ id, name }]
const query = ref('')
const results = ref([])
const sending = ref(false)
const error = ref('')
const success = ref('')

const history = ref([])
const page = ref(1)
const last = ref(1)

let searchTimer = null

const recipients = computed(() => (audience.value === 'selected' ? selected.value.length : counts[audience.value]))
const canSend = computed(() => body.value.trim().length > 0 && (recipients.value ?? 0) > 0 && !sending.value)

const isSelected = (id) => selected.value.some((m) => m.id === id)
const when = (iso) => `${formatDate(iso)} à ${new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`

async function loadCounts() {
  try {
    const [all, overdue] = await Promise.all([
      api.get('/admin/group-messages/audience', { audience: 'all' }),
      api.get('/admin/group-messages/audience', { audience: 'overdue' }),
    ])
    counts.all = all.count
    counts.overdue = overdue.count
  } catch (e) {
    error.value = e.message
  }
}

async function loadHistory(p = 1) {
  try {
    const res = await api.get('/admin/group-messages', { page: p })
    history.value = res.data
    page.value = res.current_page
    last.value = res.last_page
  } catch (e) {
    error.value = e.message
  }
}

function toggleMember(member) {
  selected.value = isSelected(member.id)
    ? selected.value.filter((m) => m.id !== member.id)
    : [...selected.value, { id: member.id, name: member.name }]
}

watch(query, (value) => {
  clearTimeout(searchTimer)
  const term = value.trim()
  if (term.length < 2) {
    results.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    try {
      const res = await api.get('/admin/members', { status: 'active', q: term, page: 1 })
      results.value = res.data
    } catch (e) {
      error.value = e.message
    }
  }, 300)
})

async function send() {
  if (!canSend.value) return
  if (!confirm(`Envoyer ce message à ${recipients.value} membre(s) ?`)) return

  sending.value = true
  error.value = ''
  success.value = ''
  try {
    const res = await api.post('/admin/group-messages', {
      audience: audience.value,
      member_ids: audience.value === 'selected' ? selected.value.map((m) => m.id) : undefined,
      body: body.value.trim(),
    })
    success.value = `Message envoyé à ${res.recipients_count} membre(s).`
    body.value = ''
    selected.value = []
    query.value = ''
    results.value = []
    await Promise.all([loadHistory(1), loadCounts()])
  } catch (e) {
    error.value = e.field('body') || e.field('member_ids') || e.message
  } finally {
    sending.value = false
  }
}

onMounted(() => {
  loadCounts()
  loadHistory()
})
</script>

<template>
  <section class="section">
    <div class="container">
      <p v-if="error" class="alert err">{{ error }}</p>
      <p v-if="success" class="alert ok">{{ success }}</p>

      <form class="card form" style="margin-bottom: 24px" @submit.prevent="send">
        <div style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: space-between; align-items: center">
          <h2 style="color: var(--green); margin: 0">Nouveau message groupé</h2>
          <RouterLink to="/messagerie" class="btn outline-green small">
            Ouvrir ma messagerie (réponses)
            <span v-if="auth.unread" class="badge-count">{{ auth.unread }}</span>
          </RouterLink>
        </div>
        <p class="muted" style="margin: 0">
          Chaque membre reçoit le message dans sa messagerie, avec une notification. S'il répond, la réponse arrive dans
          votre messagerie, et seul vous la voyez.
        </p>

        <fieldset class="gm-options" style="border: 0; padding: 0; margin: 0">
          <legend style="font-weight: 600; font-size: 14px; margin-bottom: 8px">Destinataires</legend>
          <label class="check">
            <input v-model="audience" type="radio" value="all" />
            <span>{{ AUDIENCES.all }} <span class="muted">({{ counts.all ?? '…' }})</span></span>
          </label>
          <label class="check">
            <input v-model="audience" type="radio" value="overdue" />
            <span>{{ AUDIENCES.overdue }} <span class="muted">({{ counts.overdue ?? '…' }})</span></span>
          </label>
          <label class="check">
            <input v-model="audience" type="radio" value="selected" />
            <span>{{ AUDIENCES.selected }} <span class="muted">({{ selected.length }})</span></span>
          </label>
        </fieldset>

        <div v-if="audience === 'selected'" class="form">
          <div class="field">
            <label for="gm-search">Rechercher un membre par son nom</label>
            <input id="gm-search" v-model="query" type="text" placeholder="Au moins 2 lettres" autocomplete="off" />
          </div>
          <div v-if="results.length" class="gm-results">
            <button v-for="m in results" :key="m.id" type="button" class="gm-result" :class="{ on: isSelected(m.id) }" @click="toggleMember(m)">
              <span>{{ m.name }}</span>
              <span class="muted">{{ isSelected(m.id) ? 'Retirer' : 'Ajouter' }}</span>
            </button>
          </div>
          <p v-else-if="query.trim().length >= 2" class="muted" style="margin: 0">Aucun membre actif trouvé.</p>

          <div v-if="selected.length" class="gm-chips">
            <span v-for="m in selected" :key="m.id" class="gm-chip">
              {{ m.name }}
              <button type="button" :aria-label="`Retirer ${m.name}`" @click="toggleMember(m)">×</button>
            </span>
          </div>
        </div>

        <div class="field">
          <label for="gm-body">Votre message</label>
          <textarea id="gm-body" v-model="body" rows="5" :maxlength="MAX" required></textarea>
          <span class="muted">{{ body.length }} / {{ MAX }} caractères</span>
        </div>

        <button class="btn orange" type="submit" :disabled="!canSend" style="align-self: flex-start">
          {{ sending ? 'Envoi…' : recipients ? `Envoyer à ${recipients} membre(s)` : 'Envoyer' }}
        </button>
      </form>

      <div class="card">
        <h2 style="color: var(--green)">Messages groupés envoyés</h2>
        <p v-if="!history.length" class="muted">Aucun message groupé envoyé pour l'instant.</p>

        <div v-for="g in history" :key="g.id" class="gm-item">
          <div style="display: flex; flex-wrap: wrap; gap: 6px 12px; align-items: center">
            <b>{{ when(g.created_at) }}</b>
            <span class="badge">{{ AUDIENCES[g.audience] || g.audience }}</span>
            <span class="muted">Lu par {{ g.read_count }} sur {{ g.recipients_count }}</span>
          </div>
          <p class="gm-body">{{ g.body }}</p>
        </div>

        <Pager :page="page" :last="last" @change="loadHistory" />
      </div>
    </div>
  </section>
</template>