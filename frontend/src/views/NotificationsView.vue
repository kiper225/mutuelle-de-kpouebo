<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'
import { kindOf, timeAgo, markRead, markAllRead } from '../composables/notifications'

const router = useRouter()
const items = ref([])
const loading = ref(true)
const error = ref('')

async function load() {
  try {
    items.value = (await api.get('/notifications', { limit: 50 })).items
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function go(n) {
  markRead(n)
  if (n.url) router.push(n.url)
}

async function readAll() {
  await markAllRead()
  items.value.forEach((n) => (n.read = true))
}

onMounted(load)
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Notifications</h1></div></div>
    <section class="section alt">
      <div class="container narrow">
        <div class="card">
          <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px">
            <h2 style="color: var(--green); margin: 0">Vos notifications</h2>
            <button v-if="items.some((n) => !n.read)" type="button" class="btn outline-green small" @click="readAll">Tout marquer comme lu</button>
          </div>

          <p v-if="error" class="alert err">{{ error }}</p>
          <p v-else-if="loading" class="muted">Chargement…</p>
          <p v-else-if="!items.length" class="muted">Aucune notification pour le moment.</p>

          <ul v-else class="notif-list" style="margin-top: 12px">
            <li v-for="n in items" :key="n.id">
              <button type="button" class="notif-item" :class="{ unread: !n.read }" @click="go(n)">
                <span class="notif-icon">{{ kindOf(n).icon }}</span>
                <span>
                  <span class="notif-title">{{ n.title }}</span>
                  <span v-if="n.body" class="notif-body">{{ n.body }}</span>
                  <span class="notif-time">{{ kindOf(n).label }} · {{ timeAgo(n.created_at) }}</span>
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>
    </section>
  </div>
</template>