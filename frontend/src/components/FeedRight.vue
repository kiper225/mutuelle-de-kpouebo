<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { isLoggedIn } from '../auth'
import { categoryLabel, timeAgo } from '../utils'

const props = defineProps({
  excludeSlug: { type: String, default: '' }, // publication en cours de lecture, à ne pas proposer
  title: { type: String, default: 'À lire aussi' },
})

const latest = ref([])

onMounted(async () => {
  try {
    const res = await api.get('/publications')
    latest.value = res.data.filter((p) => p.slug !== props.excludeSlug).slice(0, 4)
  } catch {
    /* la colonne reste simplement vide */
  }
})
</script>

<template>
  <aside class="fb-right" :aria-label="title">
    <h3 class="fb-side-title" style="margin-top: 0">{{ title }}</h3>
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
</template>