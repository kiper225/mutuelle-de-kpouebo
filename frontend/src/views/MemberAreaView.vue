<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { auth } from '../auth'
import { PROVIDERS, COTISATION_STATUS, providerLabel, formatMoney, formatDate, currentPeriod } from '../utils'
import Pager from '../components/Pager.vue'

const items = ref([])
const page = ref(1)
const last = ref(1)
const error = ref('')

const period = ref(currentPeriod())
const provider = ref('orange_money')
const paying = ref(false)
const payMessage = ref('')
const payError = ref('')

const statusClass = (s) => (s === 'paid' ? '' : s === 'failed' ? 'bad' : 'warn')

async function load(p = 1) {
  error.value = ''
  try {
    const res = await api.get('/cotisations', { page: p })
    items.value = res.data
    page.value = res.current_page
    last.value = res.last_page
  } catch (e) {
    error.value = e.message
  }
}

async function pay() {
  paying.value = true
  payMessage.value = ''
  payError.value = ''
  try {
    const res = await api.post('/cotisations', { period: period.value, provider: provider.value })
    if (res.payment?.payment_url) {
      window.location.href = res.payment.payment_url
      return
    }
    payMessage.value = res.payment?.message || 'Paiement enregistré.'
    await load(1)
  } catch (e) {
    payError.value = e.field('period') || e.field('provider') || e.message
  } finally {
    paying.value = false
  }
}

onMounted(() => load())
</script>

<template>
  <div class="page-banner"><div class="container"><h1>Mon espace</h1></div></div>
  <section class="section alt">
    <div class="container">
      <div class="card" style="margin-bottom: 24px">
        <h2 style="color: var(--green)">{{ auth.user.name }}</h2>
        <p class="muted">Téléphone : {{ auth.user.phone }}<span v-if="auth.user.residence"> · {{ auth.user.residence }}</span></p>
      </div>

      <div class="card" style="margin-bottom: 24px">
        <h2 style="color: var(--green)">Payer ma cotisation</h2>
        <form class="row" @submit.prevent="pay">
          <div class="field">
            <label for="period">Mois</label>
            <input id="period" v-model="period" type="month" :max="currentPeriod()" required />
          </div>
          <div class="field">
            <label for="provider">Moyen de paiement</label>
            <select id="provider" v-model="provider">
              <option v-for="(label, key) in PROVIDERS" :key="key" :value="key" :hidden="key === 'cash'" :disabled="key === 'cash'">{{ label }}</option>
            </select>
          </div>
          <div class="field" style="flex: 0 0 auto; justify-content: flex-end">
            <button class="btn orange" type="submit" :disabled="paying">{{ paying ? 'Traitement…' : 'Payer' }}</button>
          </div>
        </form>
        <p v-if="payError" class="alert err">{{ payError }}</p>
        <p v-if="payMessage" class="alert ok">{{ payMessage }}</p>
      </div>

      <div class="card">
        <h2 style="color: var(--green)">Historique de mes cotisations</h2>
        <p v-if="error" class="alert err">{{ error }}</p>
        <p v-else-if="!items.length" class="muted">Aucune cotisation enregistrée.</p>
        <div v-else class="table-wrap">
          <table>
            <thead><tr><th>Mois</th><th>Montant</th><th>Moyen</th><th>Statut</th><th>Payée le</th></tr></thead>
            <tbody>
              <tr v-for="c in items" :key="c.id">
                <td>{{ c.period }}</td>
                <td>{{ formatMoney(c.amount) }}</td>
                <td>{{ providerLabel(c.provider) }}</td>
                <td><span class="badge" :class="statusClass(c.status)">{{ COTISATION_STATUS[c.status] || c.status }}</span></td>
                <td>{{ c.paid_at ? formatDate(c.paid_at) : '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pager :page="page" :last="last" @change="load" />
      </div>
    </div>
  </section>
</template>
