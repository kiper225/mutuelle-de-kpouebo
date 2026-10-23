<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { auth, logout, refreshUnread } from '../auth'
import AdminIcon from '../components/AdminIcon.vue'

const route = useRoute()
const router = useRouter()
const open = ref(false) // menu ouvert sur téléphone
let timer = null

const groups = [
  {
    label: 'Principal',
    items: [
      { to: '/admin', label: 'Tableau de bord', icon: 'dashboard', exact: true },
      { to: '/admin/membres', label: 'Membres', icon: 'users' },
    ],
  },
  {
    label: 'Communication',
    items: [{ to: '/admin/messagerie', label: 'Messagerie', icon: 'chat', badge: true }],
  },
  {
    label: 'Contenu',
    items: [{ to: '/admin/publications', label: 'Publications', icon: 'file' }],
  },
  {
    label: 'Finances',
    items: [
      { to: '/admin/cotisations', label: 'Cotisations', icon: 'wallet' },
      { to: '/admin/donations', label: 'Dons', icon: 'heart' },
    ],
  },
  {
    label: 'Levées de fonds',
    items: [{ to: '/admin/collectes', label: 'Collectes', icon: 'gift' }],
  },
  {
    label: 'Site',
    items: [
      { to: '/admin/carrousel', label: 'Carrousel d’accueil', icon: 'image' },
      { to: '/admin/visages', label: 'Visages emblématiques', icon: 'star' },
      { to: '/admin/bureau', label: 'Bureau de la mutuelle', icon: 'users' },
      { to: '/admin/axes', label: 'Axes d’action', icon: 'target' },
      { to: '/admin/valeurs', label: 'Valeurs', icon: 'heart' },
    ],
  },
]

const title = computed(() => route.meta.title || 'Administration')
const subtitle = computed(() => route.meta.subtitle || '')
const month = new Date().toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
const initial = computed(() => (auth.user?.first_names || auth.user?.name || '?').charAt(0).toUpperCase())

const isActive = (item) => (item.exact ? route.path === item.to : route.path.startsWith(item.to))

watch(() => route.fullPath, () => {
  open.value = false
  refreshUnread()
})

onMounted(() => {
  refreshUnread()
  timer = setInterval(refreshUnread, 30000) // notification : messages non lus, mise à jour toutes les 30 secondes
})
onBeforeUnmount(() => clearInterval(timer))

async function onLogout() {
  await logout()
  router.push('/')
}
</script>

<template>
  <div class="admin-shell">
    <aside class="admin-sidebar" :class="{ open }">
      <RouterLink to="/admin" class="side-brand">
        <img src="/logo.jpg" alt="Logo de la mutuelle" />
        <div>
          <b>Kpouèbo</b>
          <span>Administration</span>
        </div>
      </RouterLink>

      <nav aria-label="Administration">
        <div v-for="g in groups" :key="g.label" class="side-group">
          <div class="side-label">{{ g.label }}</div>
          <RouterLink v-for="item in g.items" :key="item.to" :to="item.to" class="side-link" :class="{ active: isActive(item) }">
            <AdminIcon :name="item.icon" />
            {{ item.label }}
            <span v-if="item.badge && auth.unread" class="side-badge" :aria-label="`${auth.unread} message(s) non lu(s)`">
              {{ auth.unread > 99 ? '99+' : auth.unread }}
            </span>
          </RouterLink>
        </div>
      </nav>

      <div class="side-user">
        <div class="side-user-info">
          <img v-if="auth.user?.photo_url" :src="auth.user.photo_url" alt="" class="avatar" />
          <span v-else class="avatar">{{ initial }}</span>
          <div>
            <b>{{ auth.user?.name }}</b>
            <span>{{ auth.user?.phone }}</span>
          </div>
        </div>
        <RouterLink to="/profil" class="side-link"><AdminIcon name="user" /> Mon profil</RouterLink>
        <RouterLink to="/" class="side-link"><AdminIcon name="site" /> Voir le site</RouterLink>
        <button class="side-link" type="button" @click="onLogout"><AdminIcon name="logout" /> Déconnexion</button>
      </div>
    </aside>
    <div v-if="open" class="admin-scrim" @click="open = false"></div>

    <div class="admin-content">
      <header class="admin-topbar">
        <button class="icon-btn" type="button" aria-label="Ouvrir le menu" @click="open = !open">
          <AdminIcon name="menu" />
          <span v-if="auth.unread" class="dot" aria-hidden="true"></span>
        </button>
        <div class="admin-title">
          <h1>{{ title }}</h1>
          <p v-if="subtitle">{{ subtitle }}</p>
        </div>
        <div class="admin-actions">
          <span class="pill-date">{{ month }}</span>
          <RouterLink to="/admin/publications" class="btn orange small">Nouvelle publication</RouterLink>
        </div>
      </header>

      <main class="admin-main">
        <RouterView />
      </main>
    </div>
  </div>
</template>