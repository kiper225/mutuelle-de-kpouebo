<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../../api'
import { COTISATION_STATUS, providerLabel, formatMoney, formatDate, currentPeriod } from '../../utils'
import AdminNav from '../../components/AdminNav.vue'
import Pager from '../../components/Pager.vue'

const period = ref(currentPeriod())
const amount = ref('')
const overdue = ref([])
const history = ref([])
const page = ref(1)
const last = ref(1)
const error = ref('')
const success = ref('')

const statusClass = (s) => (s === 'paid' ? '' : s === 'failed' ? 'bad' : 'warn')

async function loadOverdue() {
  const res = await api.get('/admin/cotisations/overdue', { period: period.value })
  overdue.value = res.data
}

async function loadHistory(p = 1) {
  const res = await api.get('/admin/cotisations', { period: period.value, page: p })
  history.value = res.data
  page.value = res.current_page
  last.value = res.last_page
}

async function reload() {
  error.value = ''
  try {
    await Promise.all([loadOverdue(), loadHistory(1)])
  } catch (e) {
    error.value = e.message
  }
}

async function recordPaid(member) {
  error.value = ''
  success.value = ''
  try {
    await api.post('/admin/cotisations', {
      user_id: member.id,
      period: period.value,
      amount: amount.value ? Number(amount.value) : undefined,
      provider: 'cash',
    })
    success.value = `Paiement de ${member.name} enregistré pour ${period.value}.`
    await reload()
  } catch (e) {
    error.value = e.field('amount') || e.message
  }
}

onMounted(reload)
</script>

<template>
  <div class="page-banner"><div class="container"><h1>Cotisations</h1></div></div>
  <section class="section alt">
    <div class="container">
      <AdminNav />
      <p v-if="error" class="alert err">{{ error }}</p>
      <p v-if="success" class="alert ok">{{ success }}</p>

      <form class="card row" style="margin-bottom: 24px" @submit.prevent="reload">
        <div class="field">
          <label for="period">Mois</label>
          <input id="period" v-model="period" type="month" required />
        </div>
        <div class="field">
          <label for="amount">Montant pour un paiement hors ligne (FCFA, facultatif)</label>
          <input id="amount" v-model="amount" type="number" min="1" placeholder="Montant par défaut de la mutuelle" />
        </div>
        <div class="field" style="flex: 0 0 auto; justify-content: flex-end">
          <button class="btn green" type="submit">Afficher</button>
        </div>
      </form>

      <div class="card" style="margin-bottom: 24px">
        <h2 style="color: var(--green)">Membres actifs n'ayant pas payé ({{ period }})</h2>
        <p v-if="!overdue.length" class="muted">Aucun retard pour ce mois.</p>
        <div v-else class="table-wrap">
          <table>
            <thead><tr><th>Nom</th><th>Téléphone</th><th>Action</th></tr></thead>
            <tbody>
              <tr v-for="m in overdue" :key="m.id">
                <td>{{ m.name }}</td>
                <td>{{ m.phone }}</td>
                <td><button class="btn green small" @click="recordPaid(m)">Enregistrer un paiement en espèces</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="muted">La liste est limitée à 50 membres ; elle se raccourcit à mesure que les paiements sont enregistrés.</p>
      </div>

      <div class="card">
        <h2 style="color: var(--green)">Paiements et tentatives ({{ period }})</h2>
        <p v-if="!history.length" class="muted">Aucune cotisation enregistrée pour ce mois.</p>
        <div v-else class="table-wrap">
          <table>
            <thead><tr><th>Membre</th><th>Montant</th><th>Moyen</th><th>Statut</th><th>Payée le</th></tr></thead>
            <tbody>
              <tr v-for="c in history" :key="c.id">
                <td>{{ c.user?.name }}</td>
                <td>{{ formatMoney(c.amount) }}</td>
                <td>{{ providerLabel(c.provider) }}</td>
                <td><span class="badge" :class="statusClass(c.status)">{{ COTISATION_STATUS[c.status] || c.status }}</span></td>
                <td>{{ c.paid_at ? formatDate(c.paid_at) : '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pager :page="page" :last="last" @change="loadHistory" />
      </div>
    </div>
  </section>
</template>
