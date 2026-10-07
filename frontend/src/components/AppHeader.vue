<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { auth, isLoggedIn, isAdmin, logout } from '../auth'
import { site } from '../content'

const route = useRoute()
const router = useRouter()
const open = ref(null)

const menus = [
  {
    key: 'mutuelle', label: 'La mutuelle',
    items: [
      { label: 'Présentation', to: '/la-mutuelle' },
      { label: 'Notre histoire', to: '/la-mutuelle#histoire' },
      { label: 'Mot du président', to: '/la-mutuelle#president' },
      { label: 'Le bureau', to: '/la-mutuelle#bureau' },
    ],
  },
  {
    key: 'chantiers', label: 'Nos chantiers',
    items: [
      { label: 'Projets en cours', to: '/chantiers?category=projet_en_cours' },
      { label: 'Réalisations', to: '/chantiers?category=realisation' },
    ],
  },
  {
    key: 'actualites', label: 'Actualités',
    items: [
      { label: 'Toutes les actualités', to: '/actualites' },
      { label: 'Communiqués officiels', to: '/actualites?category=communique' },
    ],
  },
]

const toggle = (key) => (open.value = open.value === key ? null : key)
const close = () => (open.value = null)
const onDocClick = (e) => { if (!e.target.closest('.dropdown')) close() }
const onKey = (e) => { if (e.key === 'Escape') close() }

onMounted(() => {
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onKey)
})
watch(() => route.fullPath, close)

async function onLogout() {
  await logout()
  router.push('/')
}
</script>

<template>
  <div class="app-header">
    <div class="topbar">
      <div class="container">
        <span>{{ site.name }}</span>
        <span>Solidarité · Entraide · Développement</span>
      </div>
    </div>
    <header class="header">
      <div class="container">
        <RouterLink to="/" class="brand">
          <img src="/logo.jpg" alt="Logo de la Mutuelle de Développement de Kpouèbo"  />
          <p style="width: 120px; font-weight: bold; font-size: 12px; font-family: 'Montserrat', sans-serif; text-transform: uppercase;">{{ site.name }}</p>
        </RouterLink>
        <nav class="nav" aria-label="Navigation principale">
          <RouterLink to="/">Accueil</RouterLink>
          <div v-for="m in menus" :key="m.key" class="dropdown">
            <button type="button" class="linklike" :aria-expanded="open === m.key" @click="toggle(m.key)">{{ m.label }} ▾</button>
            <ul v-show="open === m.key" class="dropdown-menu">
              <li v-for="i in m.items" :key="i.to"><RouterLink :to="i.to">{{ i.label }}</RouterLink></li>
            </ul>
          </div>
          <RouterLink v-if="isLoggedIn && isAdmin" to="/admin" class="btn orange small">Administration</RouterLink>
          <RouterLink v-else-if="isLoggedIn" to="/espace" class="btn orange small">Mon espace</RouterLink>
          <RouterLink v-else to="/espace" class="btn orange small">Espace membres</RouterLink>
          <RouterLink to="/don" class="btn green small">Faire un don</RouterLink>
          <button v-if="isLoggedIn" class="linklike" type="button" @click="onLogout">Déconnexion ({{ auth.user.first_names || auth.user.name }})</button>
        </nav>
      </div>
    </header>
  </div>
</template>
