<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { notifState, alertState, kindOf, timeAgo, markRead, markAllRead, enableAlerts, disableAlerts } from '../composables/notifications'

const router = useRouter()
const open = ref(false)
const root = ref(null)

const alertsActive = computed(() => alertState.permission === 'granted' && alertState.enabled)

const onDocClick = (e) => {
  if (root.value && !root.value.contains(e.target)) open.value = false
}
const onKey = (e) => {
  if (e.key === 'Escape') open.value = false
}
onMounted(() => {
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onKey)
})

function go(n) {
  open.value = false
  markRead(n)
  if (n.url) router.push(n.url)
}
</script>

<template>
  <div ref="root" class="notif">
    <button type="button" class="icon-link notif-btn" :aria-expanded="open" aria-label="Notifications" title="Notifications" @click="open = !open">
      <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M6 9a6 6 0 0 1 12 0c0 5 2 6.5 2 6.5H4S6 14 6 9z" /><path d="M10 19a2 2 0 0 0 4 0" />
      </svg>
      <span v-if="notifState.unread" class="badge-count">{{ notifState.unread > 99 ? '99+' : notifState.unread }}</span>
    </button>

    <div v-show="open" class="notif-panel" role="dialog" aria-label="Notifications">
      <div class="notif-head">
        <b>Notifications</b>
        <button v-if="notifState.unread" type="button" class="linklike notif-readall" @click="markAllRead">Tout marquer comme lu</button>
      </div>

      <RouterLink v-if="notifState.messages.unread" to="/messagerie" class="notif-item unread" @click="open = false">
        <span class="notif-icon">💬</span>
        <span>
          <span class="notif-title">{{ notifState.messages.unread }} message(s) non lu(s)</span>
          <span class="notif-body">Ouvrir la messagerie</span>
        </span>
      </RouterLink>

      <p v-if="!notifState.items.length" class="muted" style="padding: 10px">Aucune notification pour le moment.</p>
      <ul v-else class="notif-list">
        <li v-for="n in notifState.items.slice(0, 8)" :key="n.id">
          <button type="button" class="notif-item" :class="{ unread: !n.read }" @click="go(n)">
            <span class="notif-icon">{{ kindOf(n).icon }}</span>
            <span>
              <span class="notif-title">{{ n.title }}</span>
              <span v-if="n.body" class="notif-body">{{ n.body }}</span>
              <span class="notif-time">{{ timeAgo(n.created_at) }}</span>
            </span>
          </button>
        </li>
      </ul>

      <RouterLink to="/notifications" class="notif-all" @click="open = false">Voir toutes les notifications</RouterLink>

      <!-- Alertes du navigateur -->
      <div v-if="alertState.supported" class="notif-alerts">
        <p v-if="alertState.permission === 'denied'" class="muted" style="margin: 0">
          Les alertes du navigateur sont bloquées. Pour les activer : cliquez sur le cadenas à gauche de l'adresse du site,
          puis autorisez les notifications.
        </p>
        <button v-else-if="alertsActive" type="button" class="btn outline-green small" style="width: 100%" @click="disableAlerts">
          Désactiver les alertes du navigateur
        </button>
        <button v-else type="button" class="btn orange small" style="width: 100%" @click="enableAlerts">
          Activer les alertes du navigateur
        </button>
      </div>
    </div>
  </div>
</template>