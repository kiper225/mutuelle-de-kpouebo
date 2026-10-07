<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { auth, isLoggedIn, isAdmin } from '../auth'
import { categoryLabel, formatDate } from '../utils'

const route = useRoute()
const pub = ref(null)
const notFound = ref(false)
const error = ref('')

const comments = ref([])
const commentPage = ref(1)
const commentLast = ref(1)
const commentError = ref('')
const newComment = ref('')
const sending = ref(false)

async function loadPublication() {
  pub.value = null
  notFound.value = false
  error.value = ''
  comments.value = []
  try {
    pub.value = await api.get(`/publications/${route.params.slug}`)
    if (isLoggedIn.value) loadComments(1)
  } catch (e) {
    if (e.status === 404) notFound.value = true
    else error.value = e.message
  }
}

async function loadComments(page) {
  commentError.value = ''
  try {
    const res = await api.get(`/publications/${pub.value.id}/comments`, { page })
    comments.value = page === 1 ? res.data : [...comments.value, ...res.data]
    commentPage.value = res.current_page
    commentLast.value = res.last_page
  } catch (e) {
    commentError.value = e.message
  }
}

async function sendComment() {
  if (!newComment.value.trim()) return
  sending.value = true
  commentError.value = ''
  try {
    const c = await api.post(`/publications/${pub.value.id}/comments`, { body: newComment.value.trim() })
    comments.value.push(c)
    newComment.value = ''
  } catch (e) {
    commentError.value = e.field('body') || e.message
  } finally {
    sending.value = false
  }
}

async function removeComment(c) {
  if (!confirm('Supprimer ce commentaire ?')) return
  try {
    await api.del(`/comments/${c.id}`)
    comments.value = comments.value.filter((x) => x.id !== c.id)
  } catch (e) {
    commentError.value = e.message
  }
}

async function toggleHidden(c) {
  try {
    const updated = await api.post(`/admin/comments/${c.id}/${c.hidden_at ? 'unhide' : 'hide'}`)
    c.hidden_at = updated.hidden_at
  } catch (e) {
    commentError.value = e.message
  }
}

watch(() => route.params.slug, loadPublication, { immediate: true })
</script>

<template>
  <section class="section">
    <div class="container narrow">
      <p><RouterLink to="/actualites">← Toutes les actualités</RouterLink></p>
      <p v-if="notFound" class="alert err">Cette publication n'existe pas ou n'est plus disponible.</p>
      <p v-else-if="error" class="alert err">{{ error }}</p>
      <p v-else-if="!pub" class="muted">Chargement…</p>

      <article v-else>
        <div class="meta">{{ formatDate(pub.published_at) }} · {{ categoryLabel(pub.category) }}</div>
        <h1 style="color: var(--green)">{{ pub.title }}</h1>
        <img v-if="pub.cover_url" :src="pub.cover_url" :alt="pub.title" class="cover-large" />
        <div class="prose">{{ pub.body }}</div>

        <hr style="margin: 40px 0; border: 0; border-top: 1px solid var(--line)" />
        <h2>Commentaires</h2>

        <p v-if="!isLoggedIn" class="muted">
          Les commentaires sont réservés aux membres.
          <RouterLink :to="{ path: '/connexion', query: { redirect: route.fullPath } }">Connectez-vous</RouterLink>
          ou <RouterLink to="/inscription">devenez membre</RouterLink>.
        </p>

        <template v-else>
          <p v-if="commentError" class="alert err">{{ commentError }}</p>
          <p v-if="!comments.length && !commentError" class="muted">Aucun commentaire pour le moment.</p>

          <div v-for="c in comments" :key="c.id" class="comment" :class="{ hidden: c.hidden_at }">
            <b>{{ c.user?.name }}</b> <span class="meta">· {{ formatDate(c.created_at) }}</span>
            <span v-if="c.hidden_at" class="badge warn" style="margin-left: 8px">Masqué</span>
            <div class="prose">{{ c.body }}</div>
            <div class="actions" style="margin-top: 8px">
              <button v-if="isAdmin" class="btn outline-green small" @click="toggleHidden(c)">
                {{ c.hidden_at ? 'Réafficher' : 'Masquer' }}
              </button>
              <button v-if="isAdmin || c.user_id === auth.user.id" class="btn danger small" @click="removeComment(c)">Supprimer</button>
            </div>
          </div>

          <button v-if="commentPage < commentLast" class="btn outline-green small" @click="loadComments(commentPage + 1)">
            Voir plus de commentaires
          </button>

          <form class="form" style="margin-top: 20px" @submit.prevent="sendComment">
            <div class="field">
              <label for="comment">Votre commentaire</label>
              <textarea id="comment" v-model="newComment" rows="3" maxlength="1000"></textarea>
            </div>
            <button class="btn green" type="submit" :disabled="sending" style="align-self: flex-start">Publier mon commentaire</button>
          </form>
        </template>
      </article>
    </div>
  </section>
</template>
