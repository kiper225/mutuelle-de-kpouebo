<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { isLoggedIn } from '../auth'
import { CATEGORIES, categoryLabel, timeAgo } from '../utils'
import PostCard from '../components/PostCard.vue'
import AdminIcon from '../components/AdminIcon.vue'

const route = useRoute()
const isChantiers = computed(() => route.path.startsWith('/chantiers'))
const CHANTIER_CATEGORIES = ['projet_en_cours', 'realisation']

const items = ref([])
const page = ref(0)
const last = ref(1)
const category = ref('')
const loading = ref(false)
const error = ref('')
const latest = ref([])

const heading = computed(() => (isChantiers.value ? 'Nos chantiers' : 'Actualités et communiqués'))

// Raccourcis de catégories (colonne de gauche et pastilles sur petit écran)
const shortcuts = computed(() => [
  { key: '', label: 'Toutes les publications', icon: 'file', to: '/actualites' },
  { key: 'actualite', label: 'Actualités', icon: 'dashboard', to: '/actualites?category=actualite' },
  { key: 'communique', label: 'Communiqués', icon: 'site', to: '/actualites?category=communique' },
  { key: 'projet_en_cours', label: 'Projets en cours', icon: 'target', to: '/chantiers?category=projet_en_cours' },
  { key: 'realisation', label: 'Réalisations', icon: 'star', to: '/chantiers?category=realisation' },
].map((s) => ({ ...s, active: s.key === category.value })))

function categoryFromRoute() {
  const c = typeof route.query.category === 'string' ? route.query.category : ''
  if (isChantiers.value) return CHANTIER_CATEGORIES.includes(c) ? c : 'projet_en_cours'
  return c in CATEGORIES ? c : ''
}

async function loadMore() {
  if (loading.value) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get('/publications', { page: page.value + 1, category: category.value })
    items.value = [...items.value, ...res.data]
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
    items.value = []
    page.value = 0
    last.value = 1
    loadMore()
  },
  { immediate: true },
)

onMounted(async () => {
  try {
    const res = await api.get('/publications')
    latest.value = res.data.slice(0, 4)
  } catch {
    /* la colonne de droite reste simplement vide */
  }
})
</script>

<template>
  <section class="fb-page">
    <div class="fb-layout">
      <!-- Colonne de gauche -->
      <aside class="fb-left" aria-label="Catégories">
        <nav>
          <RouterLink v-for="s in shortcuts" :key="s.key" :to="s.to" class="fb-link" :class="{ active: s.active }">
            <span class="fb-ico"><AdminIcon :name="s.icon" /></span>
            {{ s.label }}
          </RouterLink>
        </nav>

        <h3 class="fb-side-title">Vos raccourcis</h3>
        <nav>
          <RouterLink to="/don" class="fb-link"><span class="fb-ico"><AdminIcon name="heart" /></span>Faire un don</RouterLink>
          <RouterLink v-if="!isLoggedIn" to="/inscription" class="fb-link"><span class="fb-ico"><AdminIcon name="users" /></span>Devenir membre</RouterLink>
          <RouterLink to="/la-mutuelle" class="fb-link"><span class="fb-ico"><AdminIcon name="image" /></span>La mutuelle</RouterLink>
        </nav>
      </aside>

      <!-- Fil central -->
      <main class="fb-feed">
        <nav class="fb-chips" aria-label="Catégories">
          <RouterLink v-for="s in shortcuts" :key="s.key" :to="s.to" class="fb-chip" :class="{ active: s.active }">{{ s.label }}</RouterLink>
        </nav>

        <h1 class="fb-heading">{{ heading }}</h1>

        <p v-if="error" class="alert err">{{ error }}</p>

        <PostCard v-for="p in items" :key="p.id" :pub="p" />

        <p v-if="loading" class="muted center">Chargement…</p>
        <p v-else-if="!items.length && !error" class="fb-empty">Aucune publication pour le moment.</p>

        <div v-if="!loading && page < last" class="center">
          <button type="button" class="btn outline-green" @click="loadMore">Voir plus de publications</button>
        </div>
      </main>

      <!-- Colonne de droite -->
      <aside class="fb-right" aria-label="À la une">
        <h3 class="fb-side-title" style="margin-top: 0">À la une</h3>
        <RouterLink v-for="p in latest" :key="p.id" :to="`/actualites/${p.slug}`" class="fb-mini">
          <img v-if="p.cover_url" :src="p.cover_url" alt="" />
          <span v-else class="ph" aria-hidden="true">{{ categoryLabel(p.category).charAt(0) }}</span>
          <span>
            <b>{{ p.title }}</b>
            <span class="muted">{{ timeAgo(p.published_at) }}</span>
          </span>
        </RouterLink>

        <div class="card fb-cta">
          <template v-if="!isLoggedIn">
            <b>Rejoignez la mutuelle</b>
            <p class="muted">Devenez membre pour aimer, commenter et échanger avec les autres membres.</p>
            <RouterLink to="/inscription" class="btn orange small">Devenir membre</RouterLink>
          </template>
          <template v-else>
            <b>Soutenez la mutuelle</b>
            <p class="muted">Chaque don finance les projets du village.</p>
            <RouterLink to="/don" class="btn green small">Faire un don</RouterLink>
          </template>
        </div>
      </aside>
    </div>
  </section>
</template>