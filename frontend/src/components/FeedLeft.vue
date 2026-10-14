<script setup>
import { computed } from 'vue'
import { isLoggedIn } from '../auth'
import AdminIcon from './AdminIcon.vue'

const props = defineProps({ activeKey: { type: String, default: '' } })

const shortcuts = computed(() =>
  [
    { key: '', label: 'Toutes les publications', icon: 'file', to: '/actualites' },
    { key: 'actualite', label: 'Actualités', icon: 'dashboard', to: '/actualites?category=actualite' },
    { key: 'communique', label: 'Communiqués', icon: 'site', to: '/actualites?category=communique' },
    { key: 'projet_en_cours', label: 'Projets en cours', icon: 'target', to: '/chantiers?category=projet_en_cours' },
    { key: 'realisation', label: 'Réalisations', icon: 'star', to: '/chantiers?category=realisation' },
  ].map((s) => ({ ...s, active: s.key === props.activeKey && s.key !== '' })),
)
</script>

<template>
  <aside class="fb-left" aria-label="Catégories">
    <nav>
      <RouterLink v-for="s in shortcuts" :key="s.key" :to="s.to" class="fb-link" :class="{ active: s.active }">
        <span class="fb-ico"><AdminIcon :name="s.icon" /></span>
        {{ s.label }}
      </RouterLink>
    </nav>

    <h3 class="fb-side-title">Vos raccourcis</h3>
    <nav>
      <RouterLink to="/don" class="fb-link"><span class="fb-ico"><AdminIcon name="heart" /></span>Faire un don</RouterLink>
      <RouterLink v-if="!isLoggedIn" to="/inscription" class="fb-link"><span class="fb-ico"><AdminIcon name="users" /></span>Devenir membre</RouterLink>
      <RouterLink to="/la-mutuelle" class="fb-link"><span class="fb-ico"><AdminIcon name="image" /></span>La mutuelle</RouterLink>
    </nav>
  </aside>
</template>