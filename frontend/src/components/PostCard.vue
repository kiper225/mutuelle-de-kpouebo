<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '../api'
import { auth, isLoggedIn, isAdmin } from '../auth'
import { site } from '../content'
import { categoryLabel, timeAgo } from '../utils'

const props = defineProps({
  pub: { type: Object, required: true },
  detail: { type: Boolean, default: false }, // page de détail : texte complet et commentaires ouverts
})

const LIMIT = 280

const likes = ref(props.pub.likes_count ?? 0)
const liked = ref(!!props.pub.liked_by_me)
const commentsCount = ref(props.pub.comments_count ?? 0)

const expanded = ref(false)
const showComments = ref(props.detail)
const comments = ref([])
const commentsLoaded = ref(false)
const commentPage = ref(1)
const commentLast = ref(1)
const draft = ref('')
const sending = ref(false)
const hint = ref('')
const shareNote = ref('')
const commentInput = ref(null)

const body = computed(() => props.pub.body || '')
const isLong = computed(() => !props.detail && body.value.length > LIMIT)
const text = computed(() => (expanded.value || !isLong.value ? body.value : `${body.value.slice(0, LIMIT).trimEnd()}…`))
const initial = (name) => String(name || '?').trim().charAt(0).toUpperCase()
const detailLink = computed(() => `/actualites/${props.pub.slug}`)
const hasMoreComments = computed(() => commentPage.value < commentLast.value)

async function toggleLike() {
  if (!isLoggedIn.value) {
    hint.value = 'Connectez-vous pour aimer une publication.'
    return
  }
  hint.value = ''

  // Affichage immédiat, annulé si le serveur refuse.
  const before = { liked: liked.value, likes: likes.value }
  liked.value = !liked.value
  likes.value += liked.value ? 1 : -1

  try {
    const res = await api.post(`/publications/${props.pub.id}/like`)
    liked.value = res.liked
    likes.value = res.likes_count
  } catch (e) {
    liked.value = before.liked
    likes.value = before.likes
    hint.value = e.message
  }
}

async function loadComments(page = 1) {
  try {
    const res = await api.get(`/publications/${props.pub.id}/comments`, { page })
    comments.value = page === 1 ? res.data : [...comments.value, ...res.data]
    commentPage.value = res.current_page
    commentLast.value = res.last_page
    commentsLoaded.value = true
  } catch (e) {
    hint.value = e.message
  }
}

async function toggleComments() {
  showComments.value = !showComments.value
  if (!showComments.value) return
  if (!isLoggedIn.value) {
    hint.value = 'Connectez-vous pour lire et écrire des commentaires.'
    return
  }
  hint.value = ''
  if (!commentsLoaded.value) await loadComments()
}

// Page de détail : le bouton « Commenter » place le curseur dans le champ ; dans le fil, il ouvre les commentaires.
function onCommentClick() {
  if (props.detail) commentInput.value?.focus()
  else toggleComments()
}

async function sendComment() {
  const value = draft.value.trim()
  if (!value || sending.value) return
  sending.value = true
  hint.value = ''
  try {
    const c = await api.post(`/publications/${props.pub.id}/comments`, { body: value })
    comments.value.push(c)
    commentsCount.value++
    draft.value = ''
  } catch (e) {
    hint.value = e.field('body') || e.message
  } finally {
    sending.value = false
  }
}

async function removeComment(c) {
  if (!confirm('Supprimer ce commentaire ?')) return
  try {
    await api.del(`/comments/${c.id}`)
    comments.value = comments.value.filter((x) => x.id !== c.id)
    commentsCount.value = Math.max(0, commentsCount.value - 1)
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

async function share() {
  const url = `${window.location.origin}${detailLink.value}`
  try {
    if (navigator.share) {
      await navigator.share({ title: props.pub.title, url })
      return
    }
    await navigator.clipboard.writeText(url)
    shareNote.value = 'Lien copié.'
    setTimeout(() => (shareNote.value = ''), 2500)
  } catch {
    /* partage annulé par l'utilisateur */
  }
}

onMounted(() => {
  if (props.detail && isLoggedIn.value) loadComments()
})
</script>

<template>
  <article class="fb-post" :class="{ detail }">
    <header class="fb-post-head">
      <img src="/logo.jpg" alt="" class="fb-page-avatar" />
      <div>
        <RouterLink :to="detailLink" class="fb-page-name">{{ site.name }}</RouterLink>
        <div class="fb-sub">
          <span :title="new Date(pub.published_at).toLocaleString('fr-FR')">{{ timeAgo(pub.published_at) }}</span>
          · {{ categoryLabel(pub.category) }} ·
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18" />
          </svg>
        </div>
      </div>
    </header>

    <div class="fb-text">
      <h1 v-if="detail" class="fb-title-line fb-h1">{{ pub.title }}</h1>
      <b v-else class="fb-title-line">{{ pub.title }}</b>
      <p class="fb-body">
        {{ text }}
        <button v-if="isLong && !expanded" type="button" class="fb-more" @click="expanded = true">Voir plus</button>
      </p>
    </div>

    <template v-if="pub.cover_url">
      <a v-if="detail" :href="pub.cover_url" target="_blank" rel="noopener" class="fb-media" aria-label="Ouvrir l'image en grand">
        <img :src="pub.cover_url" :alt="pub.title" />
      </a>
      <RouterLink v-else :to="detailLink" class="fb-media" :aria-label="`Ouvrir la publication : ${pub.title}`">
        <img :src="pub.cover_url" :alt="pub.title" loading="lazy" />
      </RouterLink>
    </template>

    <div class="fb-counts">
      <span v-if="likes" class="fb-likes">
        <span class="fb-like-dot">
          <svg viewBox="0 0 24 24" width="11" height="11" fill="currentColor" aria-hidden="true"><path d="M2 10h4v11H2zM8 10l4-8c1.7 0 3 1.3 3 3v4h5.3a2 2 0 0 1 2 2.3l-1.3 7A2.5 2.5 0 0 1 18.6 21H8z" /></svg>
        </span>
        {{ likes }}
      </span>
      <span v-else></span>
      <span v-if="commentsCount && detail">{{ commentsCount }} commentaire{{ commentsCount > 1 ? 's' : '' }}</span>
      <button v-else-if="commentsCount" type="button" class="fb-count-btn" @click="toggleComments">
        {{ commentsCount }} commentaire{{ commentsCount > 1 ? 's' : '' }}
      </button>
    </div>

    <div class="fb-actions">
      <button type="button" class="fb-act" :class="{ on: liked }" :aria-pressed="liked" @click="toggleLike">
        <svg viewBox="0 0 24 24" width="20" height="20" :fill="liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
          <path d="M2 10h4v11H2zM8 10l4-8c1.7 0 3 1.3 3 3v4h5.3a2 2 0 0 1 2 2.3l-1.3 7A2.5 2.5 0 0 1 18.6 21H8z" />
        </svg>
        J'aime
      </button>
      <button type="button" class="fb-act" @click="onCommentClick">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z" />
        </svg>
        Commenter
      </button>
      <button type="button" class="fb-act" @click="share">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7" /><path d="M16 6l-4-4-4 4M12 2v13" />
        </svg>
        Partager
      </button>
    </div>

    <p v-if="shareNote" class="fb-hint" role="status">{{ shareNote }}</p>
    <p v-if="hint" class="fb-hint err" role="status">
      {{ hint }}
      <RouterLink v-if="!isLoggedIn" :to="{ path: '/connexion', query: { redirect: $route.fullPath } }">Se connecter</RouterLink>
    </p>

    <!-- Commentaires -->
    <div v-if="showComments && isLoggedIn" class="fb-comments">
      <div v-for="c in comments" :key="c.id" class="fb-comment" :style="c.hidden_at ? 'opacity: .55' : ''">
        <img v-if="c.user?.photo_url" :src="c.user.photo_url" alt="" class="fb-avatar-sm" />
        <span v-else class="fb-avatar-sm">{{ initial(c.user?.name) }}</span>
        <div>
          <div class="fb-bubble"><b>{{ c.user?.name }}</b>{{ c.body }}</div>
          <div class="fb-comment-meta">
            <span>{{ timeAgo(c.created_at) }}</span>
            <span v-if="c.hidden_at">Masqué</span>
            <button v-if="isAdmin" type="button" @click="toggleHidden(c)">{{ c.hidden_at ? 'Réafficher' : 'Masquer' }}</button>
            <button v-if="isAdmin || c.user_id === auth.user.id" type="button" @click="removeComment(c)">Supprimer</button>
          </div>
        </div>
      </div>

      <p v-if="commentsLoaded && !comments.length" class="muted" style="margin: 0 0 10px">Aucun commentaire. Soyez le premier !</p>

      <button v-if="detail && hasMoreComments" type="button" class="fb-more-comments" @click="loadComments(commentPage + 1)">
        Voir plus de commentaires
      </button>
      <p v-else-if="!detail && commentsCount > comments.length" style="margin: 0 0 10px">
        <RouterLink :to="detailLink" class="link">Voir tous les commentaires</RouterLink>
      </p>

      <form class="fb-write" @submit.prevent="sendComment">
        <img v-if="auth.user.photo_url" :src="auth.user.photo_url" alt="" class="fb-avatar-sm" />
        <span v-else class="fb-avatar-sm">{{ initial(auth.user.first_names || auth.user.name) }}</span>
        <input ref="commentInput" v-model="draft" type="text" maxlength="1000" placeholder="Écrivez un commentaire…" aria-label="Votre commentaire" />
        <button type="submit" class="btn green small" :disabled="!draft.trim() || sending">Envoyer</button>
      </form>
    </div>

    <div v-else-if="detail" class="fb-comments">
      <p class="muted" style="margin: 0">
        Les commentaires sont réservés aux membres.
        <RouterLink :to="{ path: '/connexion', query: { redirect: $route.fullPath } }">Connectez-vous</RouterLink>
        ou <RouterLink to="/inscription">devenez membre</RouterLink>.
      </p>
    </div>
  </article>
</template>