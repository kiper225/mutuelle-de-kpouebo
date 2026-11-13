<script setup>
import { ref, computed } from 'vue'
import { isLoggedIn } from '../auth'
import { alertState, enableAlerts, pushToast } from '../composables/notifications'

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
const visible = computed(() => isLoggedIn.value && alertState.supported && alertState.permission === 'default' && !postponed.value)

async function accept() {
  const permission = await enableAlerts()
  if (permission === 'denied') {
    pushToast({
      icon: '🔕',
      title: 'Alertes refusées',
      body: "Pour les activer : cliquez sur le cadenas à gauche de l'adresse du site, puis autorisez les notifications.",
    })
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
  <div v-if="visible" class="alert-prompt" role="dialog" aria-label="Activer les alertes du navigateur">
    <span class="alert-prompt-icon" aria-hidden="true">🔔</span>
    <div>
      <b>Activer les alertes du navigateur ?</b>
      <p>
        Soyez prévenu dès qu'une information, un message ou un rappel de cotisation arrive, même quand le site est dans un autre onglet.
      </p>
      <div class="actions">
        <button type="button" class="btn orange small" @click="accept">Activer</button>
        <button type="button" class="btn outline-green small" @click="later">Plus tard</button>
      </div>
    </div>
  </div>
</template>