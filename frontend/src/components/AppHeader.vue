<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { auth, isLoggedIn, isAdmin, logout, refreshUnread } from '../auth'
import { site } from '../content'
import SearchBar from './SearchBar.vue'

const route = useRoute()
const router = useRouter()
const open = ref(null)

const initial = computed(() => (auth.user?.first_names || auth.user?.name || '?').charAt(0).toUpperCase())

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
  refreshUnread()
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onKey)
})
watch(() => route.fullPath, () => {
  close()
  refreshUnread()
})

async function onLogout() {
  close()
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
        <img src="/logo.jpg" alt="Logo de la Mutuelle de Développement de Kpouèbo" />
      </RouterLink>
      <nav class="nav" aria-label="Navigation principale">
        <RouterLink to="/">Accueil</RouterLink>

        <div v-for="m in menus" :key="m.key" class="dropdown">
          <button type="button" class="linklike" :aria-expanded="open === m.key" @click="toggle(m.key)">{{ m.label }} ▾</button>
          <ul v-show="open === m.key" class="dropdown-menu">
            <li v-for="i in m.items" :key="i.to"><RouterLink :to="i.to">{{ i.label }}</RouterLink></li>
          </ul>
        </div>

        <SearchBar />

        <RouterLink v-if="isLoggedIn && isAdmin" to="/admin" class="btn orange small">Administration</RouterLink>
        <RouterLink v-else-if="isLoggedIn" to="/espace" class="btn orange small">Mon espace</RouterLink>
        <RouterLink v-else to="/espace" class="btn orange small">Espace membres</RouterLink>
        <RouterLink to="/don" class="btn green small">Faire un don</RouterLink>

        <RouterLink
          v-if="isLoggedIn"
          to="/messagerie"
          class="icon-link"
          title="Messagerie"
          :aria-label="auth.unread ? `Messagerie, ${auth.unread} message(s) non lu(s)` : 'Messagerie'"
        >
          <svg viewBox="0 0 24 24" width="35" height="35" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="M3 7l9 6 9-6" />
          </svg>
          <span v-if="auth.unread" class="badge-count" aria-hidden="true">{{ auth.unread > 99 ? '99+' : auth.unread }}</span>
        </RouterLink>

        <div v-if="isLoggedIn" class="dropdown">
          <button type="button" class="avatar-btn" :aria-expanded="open === 'account'" aria-label="Mon compte" @click="toggle('account')">
            <img v-if="auth.user.photo_url" :src="auth.user.photo_url" alt="" class="avatar" />
            <span v-else class="avatar">{{ initial }}</span>
          </button>
          <ul v-show="open === 'account'" class="dropdown-menu right">
            <li class="menu-head"><b>{{ auth.user.name }}</b></li>
            <li>
              <RouterLink v-if="isAdmin" to="/admin">Administration</RouterLink>
              <RouterLink v-else to="/espace">Mon espace</RouterLink>
            </li>
            <li><RouterLink to="/profil">Mon profil</RouterLink></li>
            <li><button type="button" class="menu-btn" @click="onLogout">Déconnexion</button></li>
          </ul>
        </div>
      </nav>
      </div>
    </header>
  </div>
</template>