<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from './components/AppHeader.vue'
import AppFooter from './components/AppFooter.vue'
import { isLoggedIn } from './auth'
import { startNotifications, stopNotifications } from './composables/notifications'
import ToastHost from './components/ToastHost.vue'

// Les notifications sont rafraîchies tant qu'un membre est connecté.
watch(isLoggedIn, (on) => (on ? startNotifications() : stopNotifications()), { immediate: true })

const route = useRoute()
// Les pages « admin » et « app » (messagerie) ont leur propre mise en page, sans l'en-tête ni le pied de page du site.
const bareLayout = computed(() => route.matched.some((r) => r.meta.layout === 'admin' || r.meta.layout === 'app'))
// Certaines pages gardent l'en-tête mais n'affichent pas de pied de page.
const noFooter = computed(() => route.matched.some((r) => r.meta.noFooter))
</script>

<template>
  <div>
    <RouterView v-if="bareLayout" />
    <div v-else>
      <AppHeader />
      <main>
        <RouterView />
      </main>
      <AppFooter v-if="!noFooter" />
    </div>
    <ToastHost />
  </div>
</template>