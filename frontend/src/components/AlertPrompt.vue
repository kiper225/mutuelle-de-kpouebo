<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { isLoggedIn } from '../auth'
import { alertState, enableAlerts, pushToast } from '../composables/notifications'

const { t } = useI18n()

const LATER_KEY = 'kp_alerts_later'
const LATER_DAYS = 3

function recentlyPostponed() {
  try {
    const at = Number(localStorage.getItem(LATER_KEY) || 0)
    return at > 0 && Date.now() - at < LATER_DAYS * 24 * 3600 * 1000
  } catch {
    return false
  }
}

const postponed = ref(recentlyPostponed())

// Affiché tant que le membre n'a pas fait de choix.
const visible = computed(
  () => isLoggedIn.value && alertState.supported && alertState.permission !== 'denied' && !alertState.chosen && !postponed.value,
)

async function accept() {
  const permission = await enableAlerts()
  if (permission === 'denied') {
    pushToast({ icon: '🔕', title: t('alerts.deniedTitle'), body: t('alerts.deniedBody') })
  }
}

function later() {
  try {
    localStorage.setItem(LATER_KEY, String(Date.now()))
  } catch {
    /* stockage indisponible : l'encadré reviendra au prochain chargement */
  }
  postponed.value = true
}
</script>

<template>
  <div v-if="visible" class="alert-prompt" role="dialog" :aria-label="$t('alerts.promptTitle')">
    <span class="alert-prompt-icon" aria-hidden="true">🔔</span>
    <div>
      <b>{{ $t('alerts.promptTitle') }}</b>
      <p>{{ $t('alerts.promptText') }}</p>
      <div class="actions">
        <button type="button" class="btn orange small" @click="accept">{{ $t('alerts.enable') }}</button>
        <button type="button" class="btn outline-green small" @click="later">{{ $t('alerts.later') }}</button>
      </div>
    </div>
  </div>
</template>