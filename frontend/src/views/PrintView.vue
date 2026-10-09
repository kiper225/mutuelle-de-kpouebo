<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import FicheAdhesion from '../components/print/FicheAdhesion.vue'
import MemberBadge from '../components/print/MemberBadge.vue'
import CartonAnnuel from '../components/print/CartonAnnuel.vue'

const TITLES = { fiche: "Fiche d'adhésion", badge: 'Carte de membre', carton: 'Carton annuel' }

const route = useRoute()
const member = ref(null)
const error = ref('')
const year = ref(Number(route.query.annee) || new Date().getFullYear())
const type = computed(() => route.params.type)
const previousTitle = document.title

function print() {
  window.print()
}

onMounted(async () => {
  if (!TITLES[type.value]) {
    error.value = 'Document inconnu.'
    return
  }
  try {
    member.value = await api.get(`/admin/members/${route.params.id}`)
    document.title = `${TITLES[type.value]} - ${member.value.name}` // nom proposé pour le fichier PDF
  } catch (e) {
    error.value = e.message
  }
})

onBeforeUnmount(() => {
  document.title = previousTitle
})
</script>

<template>
  <div>
    <div class="print-toolbar no-print">
      <h1>{{ TITLES[type] || 'Document' }}</h1>
      <label v-if="type === 'carton'" for="year">Année</label>
      <input v-if="type === 'carton'" id="year" v-model.number="year" type="number" min="2000" max="2100" style="width: 100px" />
      <button class="btn green small" type="button" :disabled="!member" @click="print">Imprimer / Enregistrer en PDF</button>
      <RouterLink v-if="member" :to="`/admin/membres/${member.id}`" class="btn outline-green small">Retour au dossier</RouterLink>
      <span class="muted">Dans la fenêtre d'impression : destination « Enregistrer au format PDF », format A4, échelle 100 %, sans en-têtes ni pieds de page.</span>
    </div>

    <p v-if="error" class="alert err no-print" style="margin: 24px">{{ error }}</p>
    <p v-else-if="!member" class="muted no-print" style="margin: 24px">Chargement…</p>

    <template v-else>
      <FicheAdhesion v-if="type === 'fiche'" :member="member" />
      <MemberBadge v-else-if="type === 'badge'" :member="member" />
      <CartonAnnuel v-else-if="type === 'carton'" :member="member" :year="year || new Date().getFullYear()" />
    </template>
  </div>
</template>