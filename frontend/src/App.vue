<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from './components/AppHeader.vue'
import AppFooter from './components/AppFooter.vue'

const route = useRoute()
// Les pages « admin » et « app » (messagerie) ont leur propre mise en page, sans l'en-tête ni le pied de page du site.
const bareLayout = computed(() => route.matched.some((r) => r.meta.layout === 'admin' || r.meta.layout === 'app'))
// Certaines pages gardent l'en-tête mais n'affichent pas de pied de page.
const noFooter = computed(() => route.matched.some((r) => r.meta.noFooter))
</script>

<template>
  <RouterView v-if="bareLayout" />
  <div v-else>
    <AppHeader />
    <main>
      <RouterView />
    </main>
    <AppFooter v-if="!noFooter" />
  </div>
</template>