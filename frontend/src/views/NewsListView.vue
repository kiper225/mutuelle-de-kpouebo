<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { CATEGORIES } from '../utils'
import PublicationCard from '../components/PublicationCard.vue'
import Pager from '../components/Pager.vue'

const route = useRoute()
const isChantiers = computed(() => route.path.startsWith('/chantiers'))
const CHANTIER_CATEGORIES = ['projet_en_cours', 'realisation']

const items = ref([])
const page = ref(1)
const last = ref(1)
const q = ref('')
const category = ref('')
const loading = ref(false)
const error = ref('')

function categoryFromRoute() {
  const c = typeof route.query.category === 'string' ? route.query.category : ''
  if (isChantiers.value) return CHANTIER_CATEGORIES.includes(c) ? c : 'projet_en_cours'
  return c in CATEGORIES ? c : ''
}

async function load(p = 1) {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get('/publications', { page: p, q: q.value.trim(), category: category.value })
    items.value = res.data
    page.value = res.current_page
    last.value = res.last_page
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

watch(
  () => [route.path, route.query.category],
  () => {
    category.value = categoryFromRoute()
    q.value = ''
    load(1)
  },
  { immediate: true },
)
</script>

<template>
  <div class="page-banner">
    <div class="container"><h1>{{ isChantiers ? 'Nos chantiers' : 'Actualités & Communiqués' }}</h1></div>
  </div>
  <section class="section">
    <div class="container">
      <nav v-if="isChantiers" class="subnav" aria-label="Type de chantier">
        <RouterLink :to="{ path: '/chantiers', query: { category: 'projet_en_cours' } }" :class="{ 'router-link-exact-active': category === 'projet_en_cours' }">Projets en cours</RouterLink>
        <RouterLink :to="{ path: '/chantiers', query: { category: 'realisation' } }" :class="{ 'router-link-exact-active': category === 'realisation' }">Réalisations</RouterLink>
      </nav>

      <form class="row" style="margin-bottom: 24px" @submit.prevent="load(1)">
        <div class="field">
          <label for="q">Rechercher</label>
          <input id="q" v-model="q" type="text" placeholder="Mots-clés" />
        </div>
        <div v-if="!isChantiers" class="field">
          <label for="cat">Catégorie</label>
          <select id="cat" v-model="category" @change="load(1)">
            <option value="">Toutes</option>
            <option v-for="(label, key) in CATEGORIES" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div class="field" style="flex: 0 0 auto; justify-content: flex-end">
          <button class="btn green" type="submit">Rechercher</button>
        </div>
      </form>

      <p v-if="error" class="alert err">{{ error }}</p>
      <p v-else-if="loading" class="muted">Chargement…</p>
      <p v-else-if="!items.length" class="muted">Aucune publication trouvée.</p>
      <div v-else class="grid">
        <PublicationCard v-for="p in items" :key="p.id" :pub="p" />
      </div>
      <Pager :page="page" :last="last" @change="load" />
    </div>
  </section>
</template>
