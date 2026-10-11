<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { auth, isAdmin, logout, refreshUnread } from '../auth'

const route = useRoute()
const router = useRouter()

const conversations = ref([])
const thread = ref(null) // { user, messages }
const draft = ref('')
const filter = ref('')
const directory = ref([])
const error = ref('')
const sending = ref(false)
const box = ref(null)
const composer = ref(null)
const searchInput = ref(null)

let poll = null
let tick = 0
let searchTimer = null
let tmpId = 0

const currentId = computed(() => Number(route.query.avec) || null)
const isTouch = window.matchMedia('(pointer: coarse)').matches

/* ---------- Utilitaires d'affichage ---------- */
const initial = (name) => String(name || '?').trim().charAt(0).toUpperCase()
const normalize = (s) => String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
const timeOf = (iso) => new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
const sameDay = (a, b) => a.toDateString() === b.toDateString()

function dayLabel(iso) {
  const d = new Date(iso)
  const now = new Date()
  const yesterday = new Date()
  yesterday.setDate(now.getDate() - 1)
  if (sameDay(d, now)) return "Aujourd'hui"
  if (sameDay(d, yesterday)) return 'Hier'
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

function listTime(iso) {
  const d = new Date(iso)
  const now = new Date()
  const yesterday = new Date()
  yesterday.setDate(now.getDate() - 1)
  if (sameDay(d, now)) return timeOf(iso)
  if (sameDay(d, yesterday)) return 'Hier'
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit' })
}

/* ---------- Données dérivées ---------- */
const filteredConversations = computed(() => {
  const f = normalize(filter.value.trim())
  return f ? conversations.value.filter((c) => normalize(c.user.name).includes(f)) : conversations.value
})

// Membres trouvés dans l'annuaire avec qui il n'y a pas encore de discussion
const others = computed(() =>
  filter.value.trim().length < 2
    ? []
    : directory.value.filter((u) => !conversations.value.some((c) => c.user.id === u.id)),
)

// Messages + séparateurs de jour ; `first` marque le premier message d'une série (il reçoit la pointe de la bulle)
const items = computed(() => {
  const out = []
  let lastDay = ''
  let lastSender = null
  for (const m of thread.value?.messages ?? []) {
    const day = new Date(m.created_at).toDateString()
    if (day !== lastDay) {
      out.push({ type: 'day', key: `d-${day}`, label: dayLabel(m.created_at) })
      lastDay = day
      lastSender = null
    }
    out.push({ type: 'msg', key: `m-${m.id}`, msg: m, mine: m.sender_id === auth.user.id, first: m.sender_id !== lastSender })
    lastSender = m.sender_id
  }
  return out
})

/* ---------- Chargement ---------- */
async function loadConversations() {
  try {
    conversations.value = await api.get('/conversations')
  } catch (e) {
    error.value = e.message
  }
}

function nearBottom() {
  const el = box.value
  return !el || el.scrollHeight - el.scrollTop - el.clientHeight < 120
}

function scrollDown() {
  nextTick(() => {
    if (box.value) box.value.scrollTop = box.value.scrollHeight
  })
}

async function loadThread({ forceScroll = false } = {}) {
  if (!currentId.value) {
    thread.value = null
    return
  }
  const stick = forceScroll || nearBottom() // on ne fait défiler que si on était déjà en bas
  try {
    const res = await api.get(`/conversations/${currentId.value}`)
    if (res.user.id !== currentId.value) return // une autre conversation a été ouverte entre-temps
    thread.value = res
    refreshUnread()
    if (stick) scrollDown()
  } catch (e) {
    error.value = e.message
    thread.value = null
  }
}

/* ---------- Actions ---------- */
function resizeComposer() {
  nextTick(() => {
    const el = composer.value
    if (!el) return
    el.style.height = 'auto'
    el.style.height = `${Math.min(el.scrollHeight, 120)}px`
  })
}

function onComposerKey(event) {
  // Ordinateur : Entrée envoie, Maj+Entrée = retour à la ligne. Téléphone : Entrée = retour à la ligne.
  if (event.key === 'Enter' && !event.shiftKey && !isTouch) {
    event.preventDefault()
    send()
  }
}

async function send() {
  const body = draft.value.trim()
  if (!body || !currentId.value || sending.value || !thread.value) return

  sending.value = true
  error.value = ''

  const temp = { id: `tmp-${++tmpId}`, sender_id: auth.user.id, body, created_at: new Date().toISOString(), read_at: null, pending: true }
  thread.value.messages.push(temp) // affichage immédiat
  draft.value = ''
  resizeComposer()
  scrollDown()

  try {
    await api.post(`/conversations/${currentId.value}`, { body })
    await Promise.all([loadThread({ forceScroll: true }), loadConversations()])
  } catch (e) {
    if (thread.value) thread.value.messages = thread.value.messages.filter((m) => m.id !== temp.id)
    draft.value = body
    error.value = e.field('body') || e.message
    resizeComposer()
  } finally {
    sending.value = false
    composer.value?.focus()
  }
}

function open(id) {
  filter.value = ''
  directory.value = []
  router.push({ path: '/messagerie', query: { avec: id } })
}

function closeChat() {
  router.push('/messagerie')
}

/* ---------- Menu du compte ---------- */
const menuOpen = ref(false)
const menuRoot = ref(null)

const onDocClick = (e) => {
  if (menuRoot.value && !menuRoot.value.contains(e.target)) menuOpen.value = false
}
const onEsc = (e) => {
  if (e.key === 'Escape') menuOpen.value = false
}

async function onLogout() {
  menuOpen.value = false
  await router.push('/') // on quitte la page avant de déconnecter
  await logout()
}

watch(() => route.fullPath, () => {
  menuOpen.value = false
})

/* ---------- Réactions ---------- */
watch(filter, (value) => {
  clearTimeout(searchTimer)
  const term = value.trim()
  if (term.length < 2) {
    directory.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    try {
      directory.value = await api.get('/directory', { q: term })
    } catch (e) {
      error.value = e.message
    }
  }, 300)
})

watch(currentId, async () => {
  error.value = ''
  thread.value = null
  draft.value = ''
  resizeComposer()
  await loadThread({ forceScroll: true })
  loadConversations() // met à jour les pastilles « non lu » de la liste
  nextTick(() => composer.value?.focus())
})

onMounted(async () => {
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onEsc)
  await loadConversations()
  await loadThread({ forceScroll: true })
  poll = setInterval(() => {
    if (document.visibilityState !== 'visible') return
    tick++
    loadThread()
    if (tick % 3 === 0) loadConversations()
  }, 5000)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onEsc)
  clearInterval(poll)
  clearTimeout(searchTimer)
})
</script>

<template>
  <section class="wa-page">
    <div class="wa" :class="{ 'has-chat': currentId }">
      <!-- Volet gauche : discussions -->
      <aside class="wa-side">
        <div class="wa-side-head">
          <div ref="menuRoot" class="wa-me">
            <button type="button" class="avatar-btn" :aria-expanded="menuOpen" aria-label="Mon compte" @click="menuOpen = !menuOpen">
              <img v-if="auth.user.photo_url" :src="auth.user.photo_url" alt="" class="avatar" />
              <span v-else class="avatar">{{ initial(auth.user.first_names || auth.user.name) }}</span>
            </button>
            <b>Discussions</b>

            <ul v-show="menuOpen" class="dropdown-menu wa-menu">
              <li class="menu-head"><b>{{ auth.user.name }}</b></li>
              <li>
                <RouterLink v-if="isAdmin" to="/admin">Administration</RouterLink>
                <RouterLink v-else to="/espace">Mon espace</RouterLink>
              </li>
              <li><RouterLink to="/profil">Mon profil</RouterLink></li>
              <li><button type="button" class="menu-btn" @click="onLogout">Déconnexion</button></li>
            </ul>
          </div>

          <RouterLink to="/" class="wa-logo" title="Retour à l'accueil" aria-label="Retour à l'accueil">
            <img src="/logo.jpg" alt="" />
          </RouterLink>
        </div>

        <div class="wa-search">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7" /><path d="M20 20l-4-4" />
          </svg>
          <input ref="searchInput" v-model="filter" type="search" placeholder="Rechercher ou démarrer une discussion" aria-label="Rechercher une discussion ou un membre" autocomplete="off" />
        </div>

        <div class="wa-list">
          <p v-if="error && !currentId" class="alert err" style="margin: 8px 12px">{{ error }}</p>

          <button
            v-for="c in filteredConversations" :key="c.user.id" type="button" class="wa-chat"
            :class="{ active: c.user.id === currentId }" @click="open(c.user.id)"
          >
            <img v-if="c.user.photo_url" :src="c.user.photo_url" alt="" class="avatar" />
            <span v-else class="avatar">{{ initial(c.user.name) }}</span>
            <span class="wa-chat-body">
              <span class="wa-chat-top">
                <b>{{ c.user.name }}</b>
                <span class="wa-time" :class="{ unread: c.unread }">{{ listTime(c.last_message.created_at) }}</span>
              </span>
              <span class="wa-chat-bottom">
                <span class="wa-preview">{{ c.last_message.mine ? 'Vous : ' : '' }}{{ c.last_message.body }}</span>
                <span v-if="c.unread" class="wa-unread">{{ c.unread > 99 ? '99+' : c.unread }}</span>
              </span>
            </span>
          </button>

          <p v-if="!conversations.length && !filter.trim()" class="muted wa-empty">
            Aucune discussion pour le moment. Recherchez un membre pour lui écrire.
          </p>

          <template v-if="others.length">
            <div class="wa-section">Autres membres</div>
            <button v-for="u in others" :key="`u${u.id}`" type="button" class="wa-chat" @click="open(u.id)">
              <img v-if="u.photo_url" :src="u.photo_url" alt="" class="avatar" />
              <span v-else class="avatar">{{ initial(u.name) }}</span>
              <span class="wa-chat-body">
                <span class="wa-chat-top"><b>{{ u.name }}</b></span>
                <span class="wa-preview">Démarrer une discussion</span>
              </span>
            </button>
          </template>
          <p v-else-if="filter.trim().length >= 2 && !filteredConversations.length" class="muted wa-empty">Aucun membre trouvé.</p>
        </div>
      </aside>

      <!-- Volet droit : conversation -->
      <section class="wa-main">
        <div v-if="!currentId" class="wa-welcome">
          <img src="/logo.jpg" alt="" />
          <h2>Messagerie de la mutuelle</h2>
          <p>Sélectionnez une discussion ou recherchez un membre pour lui écrire.</p>
        </div>

        <template v-else>
          <header class="wa-head">
            <button class="wa-icon wa-back" type="button" aria-label="Retour aux discussions" @click="closeChat">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 5l-7 7 7 7" />
              </svg>
            </button>
            <img v-if="thread?.user.photo_url" :src="thread.user.photo_url" alt="" class="avatar" />
            <span v-else class="avatar">{{ initial(thread?.user.name) }}</span>
            <b>{{ thread?.user.name || '…' }}</b>
          </header>

          <p v-if="error" class="alert err wa-alert">{{ error }}</p>

          <div ref="box" class="wa-messages">
            <p v-if="!thread" class="wa-note">Chargement…</p>
            <p v-else-if="!thread.messages.length" class="wa-note">Aucun message. Écrivez le premier !</p>

            <template v-for="it in items" :key="it.key">
              <div v-if="it.type === 'day'" class="wa-day"><span>{{ it.label }}</span></div>
              <div v-else class="wa-row" :class="{ mine: it.mine }">
                <div class="wa-bubble" :class="{ mine: it.mine, tail: it.first }">
                  <span class="prose">{{ it.msg.body }}</span>
                  <span class="wa-meta">
                    {{ timeOf(it.msg.created_at) }}
                    <span
                      v-if="it.mine" class="wa-ticks" :class="{ read: it.msg.read_at, pending: it.msg.pending }"
                      :title="it.msg.pending ? 'Envoi en cours' : it.msg.read_at ? 'Lu' : 'Envoyé'"
                    >{{ it.msg.pending ? '…' : it.msg.read_at ? '✓✓' : '✓' }}</span>
                  </span>
                </div>
              </div>
            </template>
          </div>

          <form class="wa-composer" @submit.prevent="send">
            <textarea
              ref="composer" v-model="draft" rows="1" maxlength="2000" placeholder="Écrire un message"
              aria-label="Votre message" @keydown="onComposerKey" @input="resizeComposer"
            ></textarea>
            <button class="wa-send" type="submit" :disabled="!draft.trim() || sending" aria-label="Envoyer">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">
                <path d="M3.4 20.4l17.5-7.5a1 1 0 0 0 0-1.8L3.4 3.6a1 1 0 0 0-1.4 1.2L4.1 11l9 1-9 1-2.1 6.2a1 1 0 0 0 1.4 1.2z" />
              </svg>
            </button>
          </form>
        </template>
      </section>
    </div>
  </section>
</template>