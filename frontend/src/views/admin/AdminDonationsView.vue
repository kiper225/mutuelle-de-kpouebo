<script setup>
import { reactive, ref, onMounted } from 'vue'
import { api } from '../../api'
import { PROVIDERS, COTISATION_STATUS, providerLabel, formatMoney, formatDate } from '../../utils'
import AdminNav from '../../components/AdminNav.vue'
import Pager from '../../components/Pager.vue'

const items = ref([])
const page = ref(1)
const last = ref(1)
const status = ref('')
const q = ref('')
const error = ref('')
const success = ref('')
const errors = ref({})
const saving = ref(false)

const form = reactive({ donor_name: '', donor_phone: '', amount: '', provider: 'cash', message: '' })

const statusClass = (s) => (s === 'paid' ? '' : s === 'failed' ? 'bad' : 'warn')
const err = (name) => errors.value[name]?.[0] || ''

async function load(p = 1) {
  error.value = ''
  try {
    const res = await api.get('/admin/donations', { status: status.value, q: q.value.trim(), page: p })
    items.value = res.data
    page.value = res.current_page
    last.value = res.last_page
  } catch (e) {
    error.value = e.message
  }
}

async function record() {
  saving.value = true
  errors.value = {}
  error.value = ''
  success.value = ''
  try {
    await api.post('/admin/donations', {
      donor_name: form.donor_name,
      donor_phone: form.donor_phone || undefined,
      amount: Number(form.amount),
      provider: form.provider,
      message: form.message || undefined,
    })
    success.value = 'Don enregistré.'
    Object.assign(form, { donor_name: '', donor_phone: '', amount: '', provider: 'cash', message: '' })
    await load(1)
  } catch (e) {
    errors.value = e.errors
    error.value = Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message
  } finally {
    saving.value = false
  }
}

onMounted(() => load())
</script>

<template>
  <div class="admin-donations">
    <div class="page-banner"><div class="container"><h1>Dons</h1></div></div>
    <section class="section alt">
    <div class="container">
      <AdminNav />
      <p v-if="error" class="alert err">{{ error }}</p>
      <p v-if="success" class="alert ok">{{ success }}</p>

      <form class="card form" style="margin-bottom: 24px" @submit.prevent="record">
        <h2 style="color: var(--green)">Enregistrer un don reçu hors ligne</h2>
        <div class="row">
          <div class="field">
            <label for="dn">Nom du donateur</label>
            <input id="dn" v-model="form.donor_name" type="text" required />
            <span class="error">{{ err('donor_name') }}</span>
          </div>
          <div class="field">
            <label for="dp">Téléphone (facultatif)</label>
            <input id="dp" v-model="form.donor_phone" type="tel" />
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label for="da">Montant (FCFA)</label>
            <input id="da" v-model="form.amount" type="number" min="1" required />
            <span class="error">{{ err('amount') }}</span>
          </div>
          <div class="field">
            <label for="dpv">Moyen</label>
            <select id="dpv" v-model="form.provider">
              <option v-for="(label, key) in PROVIDERS" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label for="dm">Note (facultatif)</label>
          <input id="dm" v-model="form.message" type="text" maxlength="500" />
        </div>
        <button class="btn orange" type="submit" :disabled="saving" style="align-self: flex-start">Enregistrer le don</button>
      </form>

      <div class="card">
        <h2 style="color: var(--green)">Liste des dons</h2>
        <form class="row" style="margin-bottom: 16px" @submit.prevent="load(1)">
          <div class="field">
            <label for="fs">Statut</label>
            <select id="fs" v-model="status" @change="load(1)">
              <option value="">Tous</option>
              <option value="paid">Payés</option>
              <option value="pending">En attente</option>
              <option value="failed">Échoués</option>
            </select>
          </div>
          <div class="field">
            <label for="fq">Nom du donateur</label>
            <input id="fq" v-model="q" type="text" />
          </div>
          <div class="field" style="flex: 0 0 auto; justify-content: flex-end">
            <button class="btn green" type="submit">Rechercher</button>
          </div>
        </form>

        <p v-if="!items.length" class="muted">Aucun don pour ce filtre.</p>
        <div v-else class="table-wrap">
          <table>
            <thead><tr><th>Date</th><th>Donateur</th><th>Montant</th><th>Moyen</th><th>Statut</th><th>Référence</th></tr></thead>
            <tbody>
              <tr v-for="d in items" :key="d.id">
                <td>{{ formatDate(d.created_at) }}</td>
                <td>{{ d.donor_name }}<br /><span class="muted">{{ d.donor_phone }}</span></td>
                <td>{{ formatMoney(d.amount) }}</td>
                <td>{{ providerLabel(d.provider) }}</td>
                <td><span class="badge" :class="statusClass(d.status)">{{ COTISATION_STATUS[d.status] || d.status }}</span></td>
                <td class="muted">{{ d.reference }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pager :page="page" :last="last" @change="load" />
      </div>
    </div>
    </section>
  </div>
</template>