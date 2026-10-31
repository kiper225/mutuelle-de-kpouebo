<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { PROJECT_STATUS, PROJECT_BADGE } from '../utils'

const TABS = [
  { key: '', label: 'Tous' },
  { key: 'en_cours', label: 'En cours' },
  { key: 'realise', label: 'Réalisés' },
  { key: 'a_venir', label: 'À venir' },
]
// Anciens liens du menu (?category=…) : ils ouvrent l'onglet correspondant.
const LEGACY = { projet_en_cours: 'en_cours', realisation: 'realise' }

const route = useRoute()
const items = ref([])
const loading = ref(true)
const error = ref('')

const status = computed(() => {
  const value = String(route.query.statut || LEGACY[String(route.query.category)] || '')
  return PROJECT_STATUS[value] ? value : ''
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    items.value = await api.get('/projects', { status: status.value })
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

watch(status, load, { immediate: true })
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Nos chantiers</h1></div></div>
    <section class="section alt">
      <div class="container">
        <nav class="chips-row" aria-label="Statut des chantiers">
          <RouterLink
            v-for="t in TABS" :key="t.key" :to="{ path: '/chantiers', query: t.key ? { statut: t.key } : {} }"
            class="fb-chip" :class="{ active: status === t.key }"
          >{{ t.label }}</RouterLink>
        </nav>

        <p v-if="error" class="alert err">{{ error }}</p>
        <p v-else-if="loading" class="muted center">Chargement…</p>
        <p v-else-if="!items.length" class="muted center">Aucun chantier à afficher pour le moment.</p>

        <div v-else class="fund-grid">
          <article v-for="p in items" :key="p.id" class="card fund-card">
            <img v-if="p.image_url" :src="p.image_url" :alt="p.title" class="fund-cover" loading="lazy" />
            <div v-else class="fund-cover ph">{{ p.title.charAt(0) }}</div>
            <div class="fund-body">
              <span class="badge" :class="PROJECT_BADGE[p.status]" style="align-self: flex-start">{{ PROJECT_STATUS[p.status] }}</span>
              <h3>{{ p.title }}</h3>
              <p v-if="p.summary">{{ p.summary }}</p>
              <p v-if="p.location" class="muted">Lieu : {{ p.location }}</p>

              <div v-if="p.status === 'en_cours' && p.progress !== null" style="margin-top: auto">
                <div class="progress"><span :style="{ width: p.progress + '%' }"></span></div>
                <div class="fund-meta"><span>{{ p.progress }} % réalisé</span></div>
              </div>
              <RouterLink :to="`/chantiers/${p.id}`" class="btn orange small" style="align-self: flex-start">Voir le détail</RouterLink>
            </div>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>