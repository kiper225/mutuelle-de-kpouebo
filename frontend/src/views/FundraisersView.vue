<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { formatMoney } from '../utils'

const items = ref([])
const loading = ref(true)
const error = ref('')

const percent = (f) => (f.goal_amount ? Math.min(100, Math.round(((f.raised_amount ?? 0) / f.goal_amount) * 100)) : null)

function statusLabel(f) {
  const today = new Date().toISOString().slice(0, 10)
  if (f.ends_on && String(f.ends_on).slice(0, 10) < today) return 'Terminée'
  if (f.starts_on && String(f.starts_on).slice(0, 10) > today) return 'À venir'
  return ''
}

onMounted(async () => {
  try {
    items.value = await api.get('/fundraisers')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Levées de fonds</h1></div></div>
    <section class="section alt">
      <div class="container">
        <p class="narrow center" style="margin-bottom: 32px">
          En plus des cotisations mensuelles, la mutuelle lance des collectes pour financer ses chantiers et ses activités.
          Découvrez celles qui sont en cours et contribuez à votre mesure.
        </p>

        <p v-if="error" class="alert err">{{ error }}</p>
        <p v-else-if="loading" class="muted center">Chargement…</p>
        <p v-else-if="!items.length" class="muted center">Aucune collecte pour le moment.</p>

        <div v-else class="fund-grid">
          <article v-for="f in items" :key="f.id" class="card fund-card">
            <img v-if="f.image_url" :src="f.image_url" :alt="f.title" class="fund-cover" loading="lazy" />
            <div v-else class="fund-cover ph">{{ f.title.charAt(0) }}</div>
            <div class="fund-body">
              <span v-if="statusLabel(f)" class="badge warn" style="align-self: flex-start">{{ statusLabel(f) }}</span>
              <h3>{{ f.title }}</h3>
              <p v-if="f.summary">{{ f.summary }}</p>

              <div style="margin-top: auto">
                <div v-if="f.goal_amount" class="progress"><span :style="{ width: percent(f) + '%' }"></span></div>
                <div class="fund-meta">
                  <span>{{ formatMoney(f.raised_amount ?? 0) }} collectés</span>
                  <span v-if="f.goal_amount">{{ percent(f) }} % de {{ formatMoney(f.goal_amount) }}</span>
                </div>
              </div>
              <RouterLink :to="`/collectes/${f.id}`" class="btn orange small" style="align-self: flex-start">Voir et contribuer</RouterLink>
            </div>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>