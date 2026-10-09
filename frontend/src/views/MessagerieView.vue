<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { refreshUnread } from '../auth'
import { formatDate } from '../utils'

const route = useRoute()
const router = useRouter()

const conversations = ref([])
const thread = ref(null) // { user, messages }
const draft = ref('')
const search = ref('')
const results = ref([])
const error = ref('')
const sending = ref(false)
const box = ref(null)
let poll = null
let searchTimer = null

const currentId = computed(() => Number(route.query.avec) || null)

async function loadConversations() {
  try {
    conversations.value = await api.get('/conversations')
  } catch (e) {
    error.value = e.message
  }
}

async function loadThread({ forceScroll = false } = {}) {
  if (!currentId.value) {
    thread.value = null
    return
  }
  const before = thread.value?.messages.length ?? 0
  try {
    const res = await api.get(`/conversations/${currentId.value}`)
    thread.value = res
    refreshUnread()
    if (forceScroll || res.messages.length > before) {
      await nextTick()
      if (box.value) box.value.scrollTop = box.value.scrollHeight
    }
  } catch (e) {
    error.value = e.message
    thread.value = null
  }
}

async function send() {
  const body = draft.value.trim()
  if (!body || !currentId.value) return
  sending.value = true
  error.value = ''
  try {
    await api.post(`/conversations/${currentId.value}`, { body })
    draft.value = ''
    await Promise.all([loadThread({ forceScroll: true }), loadConversations()])
  } catch (e) {
    error.value = e.field('body') || e.message
  } finally {
    sending.value = false
  }
}

function open(id) {
  search.value = ''
  results.value = []
  router.push({ path: '/messagerie', query: { avec: id } })
}

watch(search, (value) => {
  clearTimeout(searchTimer)
  if (!value.trim()) {
    results.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    try {
      results.value = await api.get('/directory', { q: value.trim() })
    } catch (e) {
      error.value = e.message
    }
  }, 300)
})

watch(currentId, () => {
  error.value = ''
  loadThread({ forceScroll: true })
})

onMounted(async () => {
  await loadConversations()
  await loadThread({ forceScroll: true })
  poll = setInterval(() => {
    loadConversations()
    loadThread()
  }, 15000) // actualisation automatique toutes les 15 secondes
})

onBeforeUnmount(() => {
  clearInterval(poll)
  clearTimeout(searchTimer)
})
</script>

<template>
  <div class="messagerie-page">
    <div class="page-banner"><div class="container"><h1>Messagerie</h1></div></div>
    <section class="section alt">
      <div class="container">
        <p v-if="error" class="alert err">{{ error }}</p>
        <div class="chat-layout">
          <aside class="chat-side card">
            <div class="field" style="margin-bottom: 12px">
              <label for="search">Écrire à un membre</label>
              <input id="search" v-model="search" type="text" placeholder="Rechercher par nom" />
            </div>
            <div v-if="results.length" style="margin-bottom: 16px">
              <button v-for="u in results" :key="u.id" class="conv" type="button" @click="open(u.id)">{{ u.name }}</button>
            </div>
            <p v-else-if="search.trim()" class="muted">Aucun membre trouvé.</p>

            <h3 style="font-size: 16px">Conversations</h3>
            <p v-if="!conversations.length" class="muted">Aucune conversation pour le moment.</p>
            <button
              v-for="c in conversations" :key="c.user.id" type="button" class="conv"
              :class="{ active: c.user.id === currentId }" @click="open(c.user.id)"
            >
              <b>{{ c.user.name }}</b>
              <span v-if="c.unread" class="badge-count">{{ c.unread }}</span><br />
              <span class="muted">{{ c.last_message.mine ? 'Vous : ' : '' }}{{ c.last_message.body.slice(0, 50) }}</span>
            </button>
          </aside>

          <div class="chat-main card">
            <p v-if="!currentId" class="muted">Choisissez une conversation ou recherchez un membre pour lui écrire.</p>
            <template v-else-if="thread">
              <h2 style="color: var(--green); font-size: 20px">{{ thread.user.name }}</h2>
              <div ref="box" class="chat-box">
                <p v-if="!thread.messages.length" class="muted">Aucun message. Écrivez le premier !</p>
                <div v-for="m in thread.messages" :key="m.id" class="bubble" :class="{ mine: m.sender_id !== thread.user.id }">
                  <div class="prose">{{ m.body }}</div>
                  <div class="bubble-date">{{ formatDate(m.created_at) }}</div>
                </div>
              </div>
              <form class="form" style="margin-top: 12px" @submit.prevent="send">
                <div class="field">
                  <label for="draft">Votre message</label>
                  <textarea id="draft" v-model="draft" rows="2" maxlength="2000"></textarea>
                </div>
                <button class="btn green" type="submit" :disabled="sending" style="align-self: flex-start">Envoyer</button>
              </form>
            </template>
            <p v-else class="muted">Chargement…</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>