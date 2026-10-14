<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import PostCard from '../components/PostCard.vue'
import FeedLeft from '../components/FeedLeft.vue'
import FeedRight from '../components/FeedRight.vue'

const route = useRoute()
const pub = ref(null)
const notFound = ref(false)
const error = ref('')
const previousTitle = document.title

async function load(slug) {
  pub.value = null
  notFound.value = false
  error.value = ''
  try {
    pub.value = await api.get(`/publications/${slug}`)
    document.title = `${pub.value.title} - ${previousTitle}`
  } catch (e) {
    if (e.status === 404) notFound.value = true
    else error.value = e.message
  }
}

watch(() => route.params.slug, (slug) => load(slug), { immediate: true })

onBeforeUnmount(() => {
  document.title = previousTitle
})
</script>

<template>
  <section class="fb-page">
    <div class="fb-layout">
      <FeedLeft :active-key="pub?.category || ''" />

      <main class="fb-feed">
        <RouterLink to="/actualites" class="fb-back">← Retour aux publications</RouterLink>

        <p v-if="notFound" class="alert err">Cette publication n'existe pas ou n'est plus disponible.</p>
        <p v-else-if="error" class="alert err">{{ error }}</p>
        <p v-else-if="!pub" class="muted center">Chargement…</p>
        <PostCard v-else :key="pub.id" :pub="pub" detail />
      </main>

      <FeedRight :key="String(route.params.slug)" :exclude-slug="String(route.params.slug)" />
    </div>
  </section>
</template>