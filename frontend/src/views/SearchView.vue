<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import SearchResults from '../components/SearchResults.vue'

const route = useRoute()
const results = ref(null)
const loading = ref(false)
const error = ref('')

watch(
  () => route.query.q,
  async (value) => {
    const term = String(value || '').trim()
    results.value = null
    error.value = ''
    if (term.length < 2) return
    loading.value = true
    try {
      results.value = await api.get('/search', { q: term, limit: 20 })
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Résultats de recherche</h1></div></div>
    <section class="section">
      <div class="container narrow">
        <p v-if="String(route.query.q || '').trim().length < 2" class="muted">Saisissez au moins 2 caractères dans la barre de recherche.</p>
        <p v-else-if="error" class="alert err">{{ error }}</p>
        <p v-else-if="loading" class="muted">Recherche…</p>
        <template v-else-if="results">
          <p class="muted">Résultats pour « {{ route.query.q }} »</p>
          <SearchResults :results="results" />
        </template>
      </div>
    </section>
  </div>
</template>