<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { PROJECT_STATUS, PROJECT_BADGE, formatMoney, formatDateShort } from '../utils'

const route = useRoute()
const p = ref(null)
const notFound = ref(false)
const error = ref('')
const previousTitle = document.title

async function load(id) {
  p.value = null
  notFound.value = false
  error.value = ''
  try {
    p.value = await api.get(`/projects/${id}`)
    document.title = `${p.value.title} - ${previousTitle}`
  } catch (e) {
    if (e.status === 404) notFound.value = true
    else error.value = e.message
  }
}

watch(() => route.params.id, (id) => load(id), { immediate: true })

onBeforeUnmount(() => {
  document.title = previousTitle
})
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>{{ p?.title || 'Chantier' }}</h1></div></div>
    <section class="section alt">
      <div class="container">
        <p><RouterLink to="/chantiers">← Tous les chantiers</RouterLink></p>

        <p v-if="notFound" class="alert err">Ce chantier n'existe pas ou n'est plus affiché.</p>
        <p v-else-if="error" class="alert err">{{ error }}</p>
        <p v-else-if="!p" class="muted">Chargement…</p>

        <div v-else class="fund-layout">
          <article class="card">
            <p v-if="p.summary" style="font-size: 18px; font-weight: 600; margin-top: 0">{{ p.summary }}</p>
            <p v-if="p.description" class="prose">{{ p.description }}</p>
            <img v-if="p.image_url" :src="p.image_url" :alt="p.title" class="fund-image" />
          </article>

          <aside class="card fund-aside">
            <span class="badge" :class="PROJECT_BADGE[p.status]">{{ PROJECT_STATUS[p.status] }}</span>

            <div v-if="p.status === 'en_cours' && p.progress !== null" style="margin: 14px 0">
              <div class="progress"><span :style="{ width: p.progress + '%' }"></span></div>
              <div class="muted" style="margin-top: 4px">{{ p.progress }} % réalisé</div>
            </div>

            <dl class="project-facts">
              <template v-if="p.location"><dt>Lieu</dt><dd>{{ p.location }}</dd></template>
              <template v-if="p.budget"><dt>Budget</dt><dd>{{ formatMoney(p.budget) }}</dd></template>
              <template v-if="p.starts_on"><dt>Début</dt><dd>{{ formatDateShort(p.starts_on) }}</dd></template>
              <template v-if="p.ends_on"><dt>Fin</dt><dd>{{ formatDateShort(p.ends_on) }}</dd></template>
            </dl>

            <div v-if="p.fundraiser" style="margin-top: 16px">
              <RouterLink :to="`/collectes/${p.fundraiser.id}`" class="btn orange" style="width: 100%">Soutenir ce chantier</RouterLink>
              <p class="muted" style="margin: 8px 0 0">Collecte : {{ p.fundraiser.title }}</p>
            </div>
          </aside>
        </div>
      </div>
    </section>
  </div>
</template>