<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../../api'
import { MEMBER_STATUS, formatDate, formatMoney } from '../../utils'
import AdminNav from '../../components/AdminNav.vue'
import Pager from '../../components/Pager.vue'

const stats = ref(null)
const members = ref([])
const page = ref(1)
const last = ref(1)
const status = ref('pending')
const q = ref('')
const error = ref('')

const badge = (s) => (s === 'active' ? '' : s === 'suspended' ? 'bad' : 'warn')

async function loadStats() {
  try {
    stats.value = await api.get('/admin/stats')
  } catch (e) {
    error.value = e.message
  }
}

async function loadMembers(p = 1) {
  error.value = ''
  try {
    const res = await api.get('/admin/members', { status: status.value, q: q.value.trim(), page: p })
    members.value = res.data
    page.value = res.current_page
    last.value = res.last_page
  } catch (e) {
    error.value = e.message
  }
}

async function act(member, action) {
  if (action === 'suspend' && !confirm(`Suspendre ${member.name} ? Il sera déconnecté.`)) return
  try {
    await api.post(`/admin/members/${member.id}/${action}`)
    await Promise.all([loadMembers(page.value), loadStats()])
  } catch (e) {
    error.value = e.message
  }
}

onMounted(() => {
  loadStats()
  loadMembers()
})
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Administration</h1></div></div>
    <section class="section alt">
      <div class="container">
        <AdminNav />
        <p v-if="error" class="alert err">{{ error }}</p>

        <div v-if="stats" class="grid" style="margin-bottom: 24px">
          <div class="card"><div class="stat">{{ stats.members_active }}</div>Membres actifs</div>
          <div class="card"><div class="stat">{{ stats.members_pending }}</div>Demandes en attente</div>
          <div class="card"><div class="stat">{{ formatMoney(stats.cotisations_month_total) }}</div>Cotisations de {{ stats.period }}</div>
          <div class="card"><div class="stat">{{ stats.publications_published }}</div>Publications en ligne</div>
          <div class="card"><div class="stat">{{ formatMoney(stats.donations_month_total) }}</div>Dons du mois</div>
          <div class="card"><div class="stat">{{ formatMoney(stats.donations_total) }}</div>Total des dons</div>
        </div>

        <div class="card">
          <h2 style="color: var(--green)">Membres</h2>
          <form class="row" style="margin-bottom: 16px" @submit.prevent="loadMembers(1)">
            <div class="field">
              <label for="status">Statut</label>
              <select id="status" v-model="status" @change="loadMembers(1)">
                <option value="pending">En attente de validation</option>
                <option value="active">Actifs</option>
                <option value="suspended">Suspendus</option>
                <option value="">Tous</option>
              </select>
            </div>
            <div class="field">
              <label for="q">Nom ou téléphone</label>
              <input id="q" v-model="q" type="text" />
            </div>
            <div class="field" style="flex: 0 0 auto; justify-content: flex-end">
              <button class="btn green" type="submit">Rechercher</button>
            </div>
          </form>

          <p v-if="!members.length" class="muted">Aucun membre pour ce filtre.</p>
          <div v-else class="table-wrap">
            <table>
              <thead><tr><th>Nom</th><th>Téléphone</th><th>Poste</th><th>Demande du</th><th>Statut</th><th>Actions</th></tr></thead>
              <tbody>
                <tr v-for="m in members" :key="m.id">
                  <td>{{ m.name }}</td>
                  <td>{{ m.phone }}</td>
                  <td>{{ m.mutuelle_role }}</td>
                  <td>{{ formatDate(m.created_at) }}</td>
                  <td><span class="badge" :class="badge(m.status)">{{ MEMBER_STATUS[m.status] || m.status }}</span></td>
                  <td>
                    <div class="actions">
                      <RouterLink :to="`/admin/membres/${m.id}`" class="btn outline-green small">Dossier</RouterLink>
                      <button v-if="m.status !== 'active'" class="btn green small" @click="act(m, 'approve')">
                        {{ m.status === 'pending' ? 'Valider' : 'Réactiver' }}
                      </button>
                      <button v-if="m.status === 'pending'" class="btn danger small" @click="act(m, 'suspend')">Refuser</button>
                      <button v-else-if="m.status === 'active'" class="btn danger small" @click="act(m, 'suspend')">Suspendre</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pager :page="page" :last="last" @change="loadMembers" />
        </div>
      </div>
    </section>
  </div>
</template>
