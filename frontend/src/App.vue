<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from './components/AppHeader.vue'
import AppFooter from './components/AppFooter.vue'

const route = useRoute()
// Les pages d'administration ont leur propre mise en page (sidebar + barre du haut).
const isAdminLayout = computed(() => route.matched.some((r) => r.meta.layout === 'admin'))
</script>

<template>
  <div v-if="isAdminLayout">
    <RouterView />
  </div>
  <div v-else>
    <AppHeader />
    <main>
      <RouterView />
    </main>
    <AppFooter />
  </div>
</template>