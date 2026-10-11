<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import SearchResults from './SearchResults.vue'

const route = useRoute()
const router = useRouter()

const root = ref(null)
const q = ref('')
const open = ref(false)
const loading = ref(false)
const error = ref('')
const results = ref(null)

let timer = null
let seq = 0 // ignore les réponses d'une recherche déjà remplacée par une plus récente

function run() {
  const term = q.value.trim()
  if (term.length < 2) {
    results.value = null
    loading.value = false
    return
  }
  const mine = ++seq
  loading.value = true
  error.value = ''
  api
    .get('/search', { q: term, limit: 4 })
    .then((res) => {
      if (mine === seq) results.value = res
    })
    .catch((e) => {
      if (mine === seq) error.value = e.message
    })
    .finally(() => {
      if (mine === seq) loading.value = false
    })
}

watch(q, () => {
  open.value = true
  clearTimeout(timer)
  timer = setTimeout(run, 300)
})

function submit() {
  const term = q.value.trim()
  if (term.length < 2) return
  open.value = false
  router.push({ path: '/recherche', query: { q: term } })
}

const onDocClick = (e) => {
  if (root.value && !root.value.contains(e.target)) open.value = false
}
const onKey = (e) => {
  if (e.key === 'Escape') open.value = false
}

watch(() => route.fullPath, () => {
  open.value = false
})

onMounted(() => {
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onKey)
  clearTimeout(timer)
})
</script>

<template>
  <div ref="root" class="searchbar">
    <form role="search" @submit.prevent="submit">
      <input
        v-model="q"
        type="search"
        placeholder="Rechercher une activité, une publication, un message…"
        aria-label="Rechercher sur la plateforme"
        autocomplete="off"
        @focus="open = true"
      />
      <button type="submit" class="search-btn" aria-label="Lancer la recherche">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <circle cx="11" cy="11" r="7" /><path d="M20 20l-4-4" />
        </svg>
      </button>
    </form>

    <div v-if="open && q.trim().length >= 2" class="search-panel" aria-live="polite">
      <p v-if="error" class="alert err">{{ error }}</p>
      <p v-else-if="loading && !results" class="muted">Recherche…</p>
      <template v-else-if="results">
        <SearchResults :results="results" />
        <p style="margin: 12px 0 0">
          <RouterLink :to="{ path: '/recherche', query: { q: q.trim() } }" class="link">Voir tous les résultats</RouterLink>
        </p>
      </template>
    </div>
  </div>
</template>