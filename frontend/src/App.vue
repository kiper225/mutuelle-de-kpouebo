<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from './components/AppHeader.vue'
import AppFooter from './components/AppFooter.vue'

const route = useRoute()
// Les pages « admin » et « app » (messagerie) ont leur propre mise en page, sans l'en-tête ni le pied de page du site.
const bareLayout = computed(() => route.matched.some((r) => r.meta.layout === 'admin' || r.meta.layout === 'app'))
</script>

<template>
  <RouterView v-if="bareLayout" />
  <div v-else>
    <AppHeader />
    <main>
      <RouterView />
    </main>
    <AppFooter />
  </div>
</template>