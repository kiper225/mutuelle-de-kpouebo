<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '../api'
import { auth, isLoggedIn, isAdmin } from '../auth'
import { timeAgo } from '../utils'

const props = defineProps({
  pubId: { type: Number, required: true },
  total: { type: Number, default: 0 },
})
const emit = defineEmits(['change']) // +1 ou -1 : le parent met à jour son compteur

const comments = ref([])
const loaded = ref(false)
const page = ref(1)
const last = ref(1)
const draft = ref('')
const sending = ref(false)
const hint = ref('')
const input = ref(null)

const hasMore = computed(() => page.value < last.value)
const initial = (name) => String(name || '?').trim().charAt(0).toUpperCase()

async function load(p = 1) {
  try {
    const res = await api.get(`/publications/${props.pubId}/comments`, { page: p })
    comments.value = p === 1 ? res.data : [...comments.value, ...res.data]
    page.value = res.current_page
    last.value = res.last_page
    loaded.value = true
  } catch (e) {
    hint.value = e.message
  }
}

async function send() {
  const value = draft.value.trim()
  if (!value || sending.value) return
  sending.value = true
  hint.value = ''
  try {
    const c = await api.post(`/publications/${props.pubId}/comments`, { body: value })
    comments.value.push(c)
    emit('change', 1)
    draft.value = ''
  } catch (e) {
    hint.value = e.field('body') || e.message
  } finally {
    sending.value = false
  }
}

async function remove(c) {
  if (!confirm('Supprimer ce commentaire ?')) return
  try {
    await api.del(`/comments/${c.id}`)
    comments.value = comments.value.filter((x) => x.id !== c.id)
    emit('change', -1)
  } catch (e) {
    hint.value = e.message
  }
}

async function toggleHidden(c) {
  try {
    const updated = await api.post(`/admin/comments/${c.id}/${c.hidden_at ? 'unhide' : 'hide'}`)
    c.hidden_at = updated.hidden_at
  } catch (e) {
    hint.value = e.message
  }
}

function focus() {
  input.value?.focus()
}

defineExpose({ focus })

onMounted(() => {
  if (isLoggedIn.value) load()
})
</script>

<template>
  <section class="card fb-panel" aria-label="Commentaires">
    <h3>Commentaires<span v-if="total"> ({{ total }})</span></h3>

    <template v-if="isLoggedIn">
      <p v-if="hint" class="fb-hint err" style="margin: 0 0 10px" role="status">{{ hint }}</p>

      <div v-for="c in comments" :key="c.id" class="fb-comment" :style="c.hidden_at ? 'opacity: .55' : ''">
        <img v-if="c.user?.photo_url" :src="c.user.photo_url" alt="" class="fb-avatar-sm" />
        <span v-else class="fb-avatar-sm">{{ initial(c.user?.name) }}</span>
        <div>
          <div class="fb-bubble"><b>{{ c.user?.name }}</b>{{ c.body }}</div>
          <div class="fb-comment-meta">
            <span>{{ timeAgo(c.created_at) }}</span>
            <span v-if="c.hidden_at">Masqué</span>
            <button v-if="isAdmin" type="button" @click="toggleHidden(c)">{{ c.hidden_at ? 'Réafficher' : 'Masquer' }}</button>
            <button v-if="isAdmin || c.user_id === auth.user.id" type="button" @click="remove(c)">Supprimer</button>
          </div>
        </div>
      </div>

      <p v-if="loaded && !comments.length" class="muted" style="margin: 0 0 10px">Aucun commentaire. Soyez le premier !</p>

      <button v-if="hasMore" type="button" class="fb-more-comments" @click="load(page + 1)">Voir plus de commentaires</button>

      <form class="fb-write" @submit.prevent="send">
        <img v-if="auth.user.photo_url" :src="auth.user.photo_url" alt="" class="fb-avatar-sm" />
        <span v-else class="fb-avatar-sm">{{ initial(auth.user.first_names || auth.user.name) }}</span>
        <input ref="input" v-model="draft" type="text" maxlength="1000" placeholder="Écrivez un commentaire…" aria-label="Votre commentaire" />
        <button type="submit" class="btn green small" :disabled="!draft.trim() || sending">Envoyer</button>
      </form>
    </template>

    <p v-else class="muted" style="margin: 0">
      Les commentaires sont réservés aux membres.
      <RouterLink :to="{ path: '/connexion', query: { redirect: $route.fullPath } }">Connectez-vous</RouterLink>
      ou <RouterLink to="/inscription">devenez membre</RouterLink>.
    </p>
  </section>
</template>