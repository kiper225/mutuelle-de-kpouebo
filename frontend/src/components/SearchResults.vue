<script setup>
import { computed } from 'vue'
import { categoryLabel, formatDateShort } from '../utils'

const props = defineProps({ results: { type: Object, required: true } })

const total = computed(
  () =>
    props.results.activities.length +
    props.results.publications.length +
    props.results.messages.length +
    props.results.members.length,
)
</script>

<template>
  <div class="search-results">
    <p v-if="!total" class="muted">Aucun résultat pour « {{ results.query }} ».</p>

    <section v-if="results.activities.length">
      <h4>Activités</h4>
      <RouterLink v-for="p in results.activities" :key="`a${p.id}`" :to="`/actualites/${p.slug}`" class="sr-item">
        <b>{{ p.title }}</b>
        <span class="muted">{{ categoryLabel(p.category) }} · {{ formatDateShort(p.published_at) }}</span>
        <span class="sr-text">{{ p.excerpt }}</span>
      </RouterLink>
    </section>

    <section v-if="results.publications.length">
      <h4>Publications</h4>
      <RouterLink v-for="p in results.publications" :key="`p${p.id}`" :to="`/actualites/${p.slug}`" class="sr-item">
        <b>{{ p.title }}</b>
        <span class="muted">{{ categoryLabel(p.category) }} · {{ formatDateShort(p.published_at) }}</span>
        <span class="sr-text">{{ p.excerpt }}</span>
      </RouterLink>
    </section>

    <section v-if="results.authenticated && (results.messages.length || results.members.length)">
      <h4>Messagerie</h4>
      <RouterLink v-for="m in results.members" :key="`u${m.id}`" :to="{ path: '/messagerie', query: { avec: m.id } }" class="sr-item">
        <b>{{ m.name }}</b>
        <span class="muted">Écrire à ce membre</span>
      </RouterLink>
      <RouterLink v-for="m in results.messages" :key="`m${m.id}`" :to="{ path: '/messagerie', query: { avec: m.with.id } }" class="sr-item">
        <b>{{ m.with.name }}</b>
        <span class="sr-text">{{ m.mine ? 'Vous : ' : '' }}{{ m.snippet }}</span>
      </RouterLink>
    </section>
    <p v-else-if="!results.authenticated" class="muted">Connectez-vous pour rechercher aussi dans votre messagerie.</p>
  </div>
</template>