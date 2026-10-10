<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '../../api'
import { formatMoney, formatDateShort } from '../../utils'
import BarChart from '../../components/BarChart.vue'

const stats = ref(null)
const monthly = ref(null)
const pending = ref([])
const error = ref('')

const nf = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })
const last = (arr) => arr?.[arr.length - 1] ?? 0
const prev = (arr) => arr?.[arr.length - 2] ?? 0
const sum = (arr) => (arr || []).reduce((a, b) => a + b, 0)
const sign = (n) => (n >= 0 ? '+' : '−')
const money = (n) => `${sign(n)}${formatMoney(Math.abs(n))}`
const percent = (n) => `${sign(n)}${nf.format(Math.abs(n))} %`

/** Écart entre le mois en cours et le mois précédent. */
function delta(arr) {
  const diff = last(arr) - prev(arr)
  const base = prev(arr)
  return { diff, pct: base > 0 ? (diff / base) * 100 : null, up: diff >= 0 }
}

const cotDelta = computed(() => delta(monthly.value?.cotisations))
const donDelta = computed(() => delta(monthly.value?.donations))
const newMembers = computed(() => last(monthly.value?.new_members))

async function load() {
  error.value = ''
  try {
    const [s, m, p] = await Promise.all([
      api.get('/admin/stats'),
      api.get('/admin/stats/monthly'),
      api.get('/admin/members', { status: 'pending', page: 1 }),
    ])
    stats.value = s
    monthly.value = m
    pending.value = p.data.slice(0, 5)
  } catch (e) {
    error.value = e.message
  }
}

async function act(member, action) {
  if (action === 'suspend' && !confirm(`Refuser la demande de ${member.name} ?`)) return
  try {
    await api.post(`/admin/members/${member.id}/${action}`)
    await load()
  } catch (e) {
    error.value = e.message
  }
}

onMounted(load)
</script>

<template>
  <section class="section">
    <div class="container">
      <p v-if="error" class="alert err">{{ error }}</p>

      <template v-if="stats && monthly">
        <div class="kpi-grid">
          <div class="kpi">
            <div class="kpi-head"><span>Membres actifs</span></div>
            <div class="kpi-value">{{ stats.members_active }}</div>
            <div class="kpi-delta">+{{ newMembers }} ce mois-ci</div>
            <div class="kpi-note">{{ stats.members_pending }} demande(s) en attente</div>
          </div>

          <div class="kpi">
            <div class="kpi-head">
              <span>Cotisations du mois</span>
              <span v-if="cotDelta.pct !== null" class="pill" :class="{ down: !cotDelta.up }">{{ percent(cotDelta.pct) }}</span>
            </div>
            <div class="kpi-value">{{ formatMoney(stats.cotisations_month_total) }}</div>
            <div class="kpi-delta" :class="{ down: !cotDelta.up }">{{ money(cotDelta.diff) }} depuis le mois dernier</div>
            <div class="kpi-note">Mois en cours : {{ stats.period }}</div>
          </div>

          <div class="kpi">
            <div class="kpi-head">
              <span>Dons du mois</span>
              <span v-if="donDelta.pct !== null" class="pill" :class="{ down: !donDelta.up }">{{ percent(donDelta.pct) }}</span>
            </div>
            <div class="kpi-value">{{ formatMoney(stats.donations_month_total) }}</div>
            <div class="kpi-delta" :class="{ down: !donDelta.up }">{{ money(donDelta.diff) }} depuis le mois dernier</div>
            <div class="kpi-note">Total des dons : {{ formatMoney(stats.donations_total) }}</div>
          </div>

          <div class="kpi">
            <div class="kpi-head"><span>Publications en ligne</span></div>
            <div class="kpi-value">{{ stats.publications_published }}</div>
            <div class="kpi-delta"><RouterLink to="/admin/publications">Gérer les publications</RouterLink></div>
            <div class="kpi-note">Actualités, projets et communiqués</div>
          </div>
        </div>

        <div class="chart-grid">
          <div class="chart-card">
            <h2>Cotisations par mois</h2>
            <div class="chart-total">{{ formatMoney(sum(monthly.cotisations)) }}</div>
            <div class="muted">12 derniers mois</div>
            <BarChart :labels="monthly.months" :values="monthly.cotisations" color="#17753F" :format-value="formatMoney" />
          </div>
          <div class="chart-card">
            <h2>Dons par mois</h2>
            <div class="chart-total">{{ formatMoney(sum(monthly.donations)) }}</div>
            <div class="muted">12 derniers mois</div>
            <BarChart :labels="monthly.months" :values="monthly.donations" color="#F0891A" :format-value="formatMoney" />
          </div>
          <div class="chart-card">
            <h2>Nouveaux membres par mois</h2>
            <div class="chart-total">{{ sum(monthly.new_members) }}</div>
            <div class="muted">12 derniers mois</div>
            <BarChart :labels="monthly.months" :values="monthly.new_members" color="#0F5A2E" :format-value="(v) => `${v} membre(s)`" />
          </div>
        </div>

        <div class="card">
          <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; align-items: center">
            <h2 style="color: var(--green); margin: 0">Demandes d'adhésion en attente</h2>
            <RouterLink :to="{ path: '/admin/membres', query: { status: 'pending' } }" class="link">Voir tout</RouterLink>
          </div>
          <p v-if="!pending.length" class="muted">Aucune demande en attente.</p>
          <div v-else class="table-wrap">
            <table>
              <thead><tr><th>Nom</th><th>Poste déclaré</th><th>Téléphone</th><th>Demande du</th><th>Actions</th></tr></thead>
              <tbody>
                <tr v-for="m in pending" :key="m.id">
                  <td>{{ m.name }}</td>
                  <td>{{ m.mutuelle_role }}</td>
                  <td>{{ m.phone }}</td>
                  <td>{{ formatDateShort(m.created_at) }}</td>
                  <td>
                    <div class="actions">
                      <RouterLink :to="`/admin/membres/${m.id}`" class="btn outline-green small">Dossier</RouterLink>
                      <button class="btn green small" @click="act(m, 'approve')">Valider</button>
                      <button class="btn danger small" @click="act(m, 'suspend')">Refuser</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
      <p v-else-if="!error" class="muted">Chargement…</p>
    </div>
  </section>
</template>