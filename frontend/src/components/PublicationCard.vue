<script setup>
import { categoryLabel, formatDate } from '../utils'
defineProps({ pub: { type: Object, required: true } })
</script>

<template>
  <article class="card pub">
    <img v-if="pub.cover_url" :src="pub.cover_url" :alt="pub.title" class="pub-cover" />
    <div v-else class="pub-cover ph">Pas d'image</div>
    <div class="pub-body">
      <div class="meta">{{ formatDate(pub.published_at) }} · {{ categoryLabel(pub.category) }}</div>
      <h3>{{ pub.title }}</h3>
      <p v-if="pub.excerpt">{{ pub.excerpt }}</p>
      <RouterLink :to="`/actualites/${pub.slug}`" class="link">Lire la suite</RouterLink>
    </div>
  </article>
</template>
