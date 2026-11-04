<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { auth, isAdmin, logout, refreshUnread } from '../auth'
import { STICKERS } from '../stickers'
import PresenceAvatar from '../components/PresenceAvatar.vue'
import EmojiPanel from '../components/EmojiPanel.vue'

const route = useRoute()
const router = useRouter()

const conversations = ref([])
const thread = ref(null) // { user, blocked_by_me, messages }
const draft = ref('')
const filter = ref('')
const directory = ref([])
const error = ref('')
const sending = ref(false)
const box = ref(null)
const composer = ref(null)
const searchInput = ref(null)

// Profil du membre, panneau d'emojis
const infoOpen = ref(false)
const info = ref(null)
const infoLoading = ref(false)
const infoError = ref('')
const busy = ref(false)
const emojiOpen = ref(false)

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
const sticker = (id) => STICKERS[id] || { emoji: '❔', label: '' }

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

/** « En ligne », « Actif il y a 5 min » ou « Actif il y a 3 h » (vide si le membre n'a pas été actif depuis 24 h ou masque son statut). */
function presenceText(u) {
  if (!u) return ''
  if (u.online) return 'En ligne'
  const m = u.last_seen_minutes
  if (m === null || m === undefined) return ''
  if (m < 60) return `Actif il y a ${m} min`
  return `Actif il y a ${Math.floor(m / 60)} h`
}

/** Texte affiché dans la liste des discussions. */
const previewOf = (m) => (m.kind === 'sticker' ? `Autocollant : ${sticker(m.body).label}` : m.body)

/** Un message qui ne contient que 1 à 3 emojis s'affiche en grand. */
const EMOJI_ONLY = /^(?:\p{Extended_Pictographic}[\u{1F3FB}-\u{1F3FF}\uFE0F\u200D\p{Extended_Pictographic}]*|\s)+$/u
function isBigEmoji(body) {
  const text = String(body || '').trim()
  if (!text || !EMOJI_ONLY.test(text)) return false
  const count = typeof Intl.Segmenter === 'function' ? [...new Intl.Segmenter().segment(text)].length : text.length
  return count <= 3
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

/* ---------- Envoi ---------- */
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

/** Envoie un message texte (kind = 'text') ou un autocollant (kind = 'sticker', body = identifiant). */
async function submit(body, kind) {
  if (!currentId.value || sending.value || !thread.value || thread.value.blocked_by_me) return

  sending.value = true
  error.value = ''

  const temp = { id: `tmp-${++tmpId}`, sender_id: auth.user.id, body, kind, created_at: new Date().toISOString(), read_at: null, pending: true }
  thread.value.messages.push(temp) // affichage immédiat
  if (kind === 'text') {
    draft.value = ''
    resizeComposer()
  }
  scrollDown()

  try {
    await api.post(`/conversations/${currentId.value}`, { body, kind })
    await Promise.all([loadThread({ forceScroll: true }), loadConversations()])
  } catch (e) {
    if (thread.value) thread.value.messages = thread.value.messages.filter((m) => m.id !== temp.id)
    if (kind === 'text') {
      draft.value = body
      resizeComposer()
    }
    error.value = e.field('body') || e.message
  } finally {
    sending.value = false
    if (!isTouch) composer.value?.focus()
  }
}

function send() {
  const body = draft.value.trim()
  if (!body) return
  emojiOpen.value = false
  submit(body, 'text')
}

function sendSticker(id) {
  emojiOpen.value = false
  submit(id, 'sticker')
}

/** Insère un emoji à l'endroit du curseur. */
function insertEmoji(emoji) {
  const el = composer.value
  const start = el?.selectionStart ?? draft.value.length
  const end = el?.selectionEnd ?? start
  draft.value = (draft.value.slice(0, start) + emoji + draft.value.slice(end)).slice(0, 2000)
  nextTick(() => {
    if (el) {
      const pos = Math.min(start + emoji.length, draft.value.length)
      if (!isTouch) el.focus()
      el.setSelectionRange(pos, pos)
    }
    resizeComposer()
  })
}

/* ---------- Navigation, profil, blocage, suppression ---------- */
function open(id) {
  filter.value = ''
  directory.value = []
  router.push({ path: '/messagerie', query: { avec: id } })
}

function closeChat() {
  infoOpen.value = false
  emojiOpen.value = false
  router.push('/messagerie')
}

async function openInfo() {
  if (!currentId.value) return
  infoOpen.value = true
  infoLoading.value = true
  infoError.value = ''
  info.value = null
  try {
    info.value = await api.get(`/members/${currentId.value}`)
  } catch (e) {
    infoError.value = e.message
  } finally {
    infoLoading.value = false
  }
}

async function toggleBlock() {
  if (!info.value || busy.value) return
  const blocking = !info.value.blocked_by_me
  if (
    blocking &&
    !confirm(`Bloquer ${info.value.name} ?\n\nCe membre ne pourra plus vous écrire et ne verra plus quand vous êtes en ligne. Vous pourrez le débloquer à tout moment.`)
  ) {
    return
  }
  busy.value = true
  infoError.value = ''
  try {
    if (blocking) await api.post(`/conversations/${currentId.value}/block`)
    else await api.del(`/conversations/${currentId.value}/block`)
    info.value.blocked_by_me = blocking
    await Promise.all([loadThread(), loadConversations()])
  } catch (e) {
    infoError.value = e.message
  } finally {
    busy.value = false
  }
}

async function unblockQuick() {
  try {
    await api.del(`/conversations/${currentId.value}/block`)
    if (info.value) info.value.blocked_by_me = false
    await Promise.all([loadThread(), loadConversations()])
  } catch (e) {
    error.value = e.message
  }
}

async function removeChat(id, name) {
  if (!confirm(`Supprimer la discussion avec ${name} ?\n\nElle sera supprimée pour vous seulement : l'autre membre garde la sienne.`)) return
  error.value = ''
  try {
    await api.del(`/conversations/${id}`)
    if (currentId.value === id) closeChat()
    conversations.value = conversations.value.filter((c) => c.user.id !== id)
    refreshUnread()
  } catch (e) {
    error.value = e.message
  }
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
  infoOpen.value = false
  emojiOpen.value = false
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

          <div v-for="c in filteredConversations" :key="c.user.id" class="wa-chat-item">
            <button type="button" class="wa-chat" :class="{ active: c.user.id === currentId }" @click="open(c.user.id)">
              <PresenceAvatar :name="c.user.name" :photo="c.user.photo_url" :online="c.user.online" :size="48" />
              <span class="wa-chat-body">
                <span class="wa-chat-top">
                  <b>{{ c.user.name }}</b>
                  <span class="wa-time" :class="{ unread: c.unread }">{{ listTime(c.last_message.created_at) }}</span>
                </span>
                <span class="wa-chat-bottom">
                  <span class="wa-preview">{{ c.last_message.mine ? 'Vous : ' : '' }}{{ previewOf(c.last_message) }}</span>
                  <span v-if="c.unread" class="wa-unread">{{ c.unread > 99 ? '99+' : c.unread }}</span>
                </span>
              </span>
            </button>
            <button
              type="button" class="wa-chat-x" :aria-label="`Supprimer la discussion avec ${c.user.name}`"
              title="Supprimer la discussion" @click.stop="removeChat(c.user.id, c.user.name)"
            >×</button>
          </div>

          <p v-if="!conversations.length && !filter.trim()" class="muted wa-empty">
            Aucune discussion pour le moment. Recherchez un membre pour lui écrire.
          </p>

          <template v-if="others.length">
            <div class="wa-section">Autres membres</div>
            <button v-for="u in others" :key="`u${u.id}`" type="button" class="wa-chat" @click="open(u.id)">
              <PresenceAvatar :name="u.name" :photo="u.photo_url" :online="u.online" :size="48" />
              <span class="wa-chat-body">
                <span class="wa-chat-top"><b>{{ u.name }}</b></span>
                <span class="wa-preview">{{ presenceText(u) || 'Démarrer une discussion' }}</span>
              </span>
            </button>
          </template>
          <p v-else-if="filter.trim().length >= 2 && !filteredConversations.length" class="muted wa-empty">Aucun membre trouvé.</p>
        </div>
      </aside>

      <!-- Volet central : conversation -->
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

            <button type="button" class="wa-head-btn" title="Voir le profil du membre" @click="openInfo">
              <PresenceAvatar :name="thread?.user.name" :photo="thread?.user.photo_url" :online="thread?.user.online" :size="42" />
              <span class="wa-head-name">
                <b>{{ thread?.user.name || '…' }}</b>
                <span v-if="presenceText(thread?.user)" class="wa-status" :class="{ off: !thread.user.online }">{{ presenceText(thread?.user) }}</span>
              </span>
            </button>

            <button type="button" class="wa-icon wa-close" aria-label="Fermer la discussion" title="Fermer la discussion" @click="closeChat">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
              </svg>
            </button>
          </header>

          <p v-if="error" class="alert err wa-alert">{{ error }}</p>

          <div ref="box" class="wa-messages">
            <p v-if="!thread" class="wa-note">Chargement…</p>
            <p v-else-if="!thread.messages.length" class="wa-note">Aucun message. Écrivez le premier !</p>

            <template v-for="it in items" :key="it.key">
              <div v-if="it.type === 'day'" class="wa-day"><span>{{ it.label }}</span></div>

              <div v-else class="wa-row" :class="{ mine: it.mine }">
                <!-- Autocollant -->
                <div v-if="it.msg.kind === 'sticker'" class="wa-sticker-wrap" :class="{ mine: it.mine }">
                  <div class="wa-sticker">
                    <span class="wa-sticker-emoji">{{ sticker(it.msg.body).emoji }}</span>
                    <span class="wa-sticker-label">{{ sticker(it.msg.body).label }}</span>
                  </div>
                  <span class="wa-meta">
                    {{ timeOf(it.msg.created_at) }}
                    <span
                      v-if="it.mine" class="wa-ticks" :class="{ read: it.msg.read_at, pending: it.msg.pending }"
                      :title="it.msg.pending ? 'Envoi en cours' : it.msg.read_at ? 'Lu' : 'Envoyé'"
                    >{{ it.msg.pending ? '…' : it.msg.read_at ? '✓✓' : '✓' }}</span>
                  </span>
                </div>

                <!-- Message texte -->
                <div v-else class="wa-bubble" :class="{ mine: it.mine, tail: it.first, big: isBigEmoji(it.msg.body) }">
                  <span v-if="it.msg.group_message_id" class="wa-tag">Message groupé</span>
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

          <!-- Membre bloqué : on remplace la zone de saisie -->
          <div v-if="thread?.blocked_by_me" class="wa-blocked">
            <span>Vous avez bloqué ce membre.</span>
            <button type="button" class="btn green small" @click="unblockQuick">Débloquer</button>
          </div>

          <template v-else>
            <EmojiPanel v-if="emojiOpen" @pick="insertEmoji" @sticker="sendSticker" @close="emojiOpen = false" />

            <form class="wa-composer" @submit.prevent="send">
              <button
                type="button" class="wa-icon wa-emoji-btn" :class="{ on: emojiOpen }" :aria-expanded="emojiOpen"
                aria-label="Emojis et autocollants" title="Emojis et autocollants" @click="emojiOpen = !emojiOpen"
              >
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="9" /><path d="M8.5 14c.9 1.3 2.1 2 3.5 2s2.6-.7 3.5-2" /><path d="M9 9.5h.01M15 9.5h.01" stroke-width="2.5" />
                </svg>
              </button>
              <textarea
                ref="composer" v-model="draft" rows="1" maxlength="2000" placeholder="Écrire un message"
                aria-label="Votre message" @keydown="onComposerKey" @input="resizeComposer"
              ></textarea>
              <button class="wa-send" type="submit" :disabled="!draft.trim() || sending" aria-label="Envoyer">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true">
                  <polygon points="3.5,4 20.5,12 3.5,20 7.5,12" />
                </svg>
              </button>
            </form>
          </template>
        </template>
      </section>

      <!-- Volet droit : profil du membre -->
      <aside v-if="infoOpen && currentId" class="wa-info" aria-label="Profil du membre">
        <div class="wa-info-head">
          <button type="button" class="wa-icon" aria-label="Fermer le profil" @click="infoOpen = false">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg>
          </button>
          <b>Profil du membre</b>
        </div>

        <p v-if="infoLoading" class="wa-note" style="margin: 16px">Chargement…</p>
        <p v-else-if="infoError && !info" class="alert err" style="margin: 16px">{{ infoError }}</p>

        <div v-else-if="info" class="wa-info-body">
          <PresenceAvatar :name="info.name" :photo="info.photo_url" :online="info.online" :size="120" />
          <h2>{{ info.name }}</h2>
          <span v-if="presenceText(info)" class="wa-status" :class="{ off: !info.online }">{{ presenceText(info) }}</span>

          <dl class="project-facts">
            <template v-if="info.is_admin"><dt>Rôle</dt><dd>Administration de la mutuelle</dd></template>
            <template v-else-if="info.mutuelle_role"><dt>Poste</dt><dd>{{ info.mutuelle_role }}</dd></template>
            <template v-if="info.member_since"><dt>Membre depuis</dt><dd>{{ info.member_since }}</dd></template>
          </dl>

          <div class="wa-info-actions">
            <p v-if="infoError" class="alert err">{{ infoError }}</p>
            <p v-if="info.blocked_by_me" class="alert err">Vous avez bloqué ce membre.</p>

            <button v-if="info.can_block" type="button" class="btn small" :class="info.blocked_by_me ? 'green' : 'danger'" :disabled="busy" @click="toggleBlock">
              {{ info.blocked_by_me ? 'Débloquer ce membre' : 'Bloquer ce membre' }}
            </button>
            <p v-else class="muted" style="margin: 0">L'administration ne peut pas être bloquée.</p>

            <button type="button" class="btn danger small" @click="removeChat(currentId, info.name)">Supprimer la discussion</button>
          </div>
        </div>
      </aside>
    </div>
  </section>
</template>