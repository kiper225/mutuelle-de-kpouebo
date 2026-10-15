<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
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
    /* la liste reste simplement vide */
  }
})
</script>

<template>
  <aside class="fb-right" aria-label="Don et commentaires">
    <div class="card fb-cta" style="margin-top: 0">
      <b>Soutenez la mutuelle</b>
      <p class="muted">Chaque don finance les projets du village.</p>
      <RouterLink to="/don" class="btn green small">Faire un don</RouterLink>
    </div>

    <slot />

    <template v-if="latest.length">
      <h3 class="fb-side-title">{{ title }}</h3>
      <RouterLink v-for="p in latest" :key="p.id" :to="`/actualites/${p.slug}`" class="fb-mini">
        <img v-if="p.cover_url" :src="p.cover_url" alt="" />
        <span v-else class="ph" aria-hidden="true">{{ categoryLabel(p.category).charAt(0) }}</span>
        <span>
          <b>{{ p.title }}</b>
          <span class="muted">{{ timeAgo(p.published_at) }}</span>
        </span>
      </RouterLink>
    </template>
  </aside>
</template>