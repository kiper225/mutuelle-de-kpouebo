<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { formatMoney } from '../utils'

const route = useRoute()
const reference = computed(() => String(route.query.reference || route.query.trxref || ''))
const info = ref(null)
const error = ref('')
let timer = null
let tries = 0

function stop() {
  clearInterval(timer)
  timer = null
}

async function check() {
  if (!reference.value) {
    error.value = 'Référence de paiement manquante.'
    stop()
    return
  }
  try {
    info.value = await api.get(`/payments/${encodeURIComponent(reference.value)}`)
  } catch (e) {
    error.value = e.message
    stop()
    return
  }
  tries++
  if (info.value.status !== 'pending' || tries >= 10) stop()
}

onMounted(() => {
  check()
  timer = setInterval(check, 3000)
})
onBeforeUnmount(stop)
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Résultat du paiement</h1></div></div>
    <section class="section alt">
      <div class="container narrow">
        <div class="card">
          <p v-if="error" class="alert err">{{ error }}</p>
          <p v-else-if="!info" class="muted">Vérification du paiement…</p>

          <template v-else-if="info.status === 'paid'">
            <p class="alert ok">
              Paiement confirmé : {{ formatMoney(info.amount) }}
              ({{ info.type === 'don' ? 'don' : 'cotisation' }}). Merci !
            </p>
            <p class="muted">Référence : {{ info.reference }}</p>
            <RouterLink :to="info.type === 'don' ? '/' : '/espace'" class="btn green">
              {{ info.type === 'don' ? "Retour à l'accueil" : 'Voir mes cotisations' }}
            </RouterLink>
          </template>

          <template v-else-if="info.status === 'failed'">
            <p class="alert err">Le paiement n'a pas abouti. Aucun montant n'a été validé.</p>
            <p class="muted">Référence : {{ info.reference }}</p>
            <RouterLink :to="info.type === 'don' ? '/don' : '/espace'" class="btn orange">Réessayer</RouterLink>
          </template>

          <template v-else>
            <p class="alert ok">Paiement en cours de confirmation…</p>
            <p>
              Si vous avez bien payé, la confirmation peut prendre quelques minutes. Vous pouvez recharger cette page
              plus tard avec la référence <b>{{ info.reference }}</b>.
            </p>
            <button class="btn green" type="button" @click="check">Vérifier à nouveau</button>
          </template>
        </div>
      </div>
    </section>
  </div>
</template>