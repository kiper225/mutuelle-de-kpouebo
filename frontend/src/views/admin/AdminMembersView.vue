<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../../api'
import { MEMBER_STATUS, formatDate } from '../../utils'
import Pager from '../../components/Pager.vue'

const route = useRoute()
const members = ref([])
const page = ref(1)
const last = ref(1)
const status = ref(typeof route.query.status === 'string' ? route.query.status : '')
const q = ref('')
const error = ref('')

const badge = (s) => (s === 'active' ? '' : s === 'suspended' ? 'bad' : 'warn')
const initial = (m) => (m.first_names || m.name || '?').charAt(0).toUpperCase()

async function load(p = 1) {
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
    await load(page.value)
  } catch (e) {
    error.value = e.message
  }
}

onMounted(() => load())
</script>

<template>
  <section class="section">
    <div class="container">
      <p v-if="error" class="alert err">{{ error }}</p>

      <div class="card">
        <form class="row" style="margin-bottom: 16px" @submit.prevent="load(1)">
          <div class="field">
            <label for="status">Statut</label>
            <select id="status" v-model="status" @change="load(1)">
              <option value="">Tous</option>
              <option value="pending">En attente de validation</option>
              <option value="active">Actifs</option>
              <option value="suspended">Suspendus</option>
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
            <thead>
              <tr><th>Membre</th><th>Poste</th><th>Téléphone</th><th>N° de membre</th><th>Demande du</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <tr v-for="m in members" :key="m.id">
                <td>
                  <div style="display: flex; align-items: center; gap: 10px">
                    <img v-if="m.photo_url" :src="m.photo_url" alt="" class="avatar" />
                    <span v-else class="avatar">{{ initial(m) }}</span>
                    {{ m.name }}
                  </div>
                </td>
                <td>{{ m.mutuelle_role }}</td>
                <td>{{ m.phone }}</td>
                <td>{{ m.member_number || '—' }}</td>
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
        <Pager :page="page" :last="last" @change="load" />
      </div>
    </div>
  </section>
</template>