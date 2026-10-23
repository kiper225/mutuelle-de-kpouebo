<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api'
import { isLoggedIn } from '../auth'
import { site } from '../content'
import { PROVIDERS, formatMoney, formatDateShort } from '../utils'

const route = useRoute()
const providers = Object.entries(PROVIDERS).filter(([key]) => key !== 'cash')

const f = ref(null)
const notFound = ref(false)
const error = ref('')

const form = reactive({ amount: '', provider: 'orange_money' })
const sending = ref(false)
const payError = ref('')
const result = ref(null)

const percent = computed(() =>
  f.value?.goal_amount ? Math.min(100, Math.round(((f.value.raised_amount ?? 0) / f.value.goal_amount) * 100)) : null,
)

const closedReason = computed(() => {
  if (!f.value || f.value.is_open) return ''
  const today = new Date().toISOString().slice(0, 10)
  if (f.value.starts_on && String(f.value.starts_on).slice(0, 10) > today) return "Cette collecte n'a pas encore commencé."
  return 'Cette collecte est terminée.'
})

async function load(id) {
  f.value = null
  notFound.value = false
  error.value = ''
  result.value = null
  try {
    f.value = await api.get(`/fundraisers/${id}`)
    form.amount = f.value.suggested_amount ?? ''
  } catch (e) {
    if (e.status === 404) notFound.value = true
    else error.value = e.message
  }
}

async function contribute() {
  sending.value = true
  payError.value = ''
  try {
    const res = await api.post(`/fundraisers/${f.value.id}/contribute`, {
      amount: Number(form.amount),
      provider: form.provider,
    })
    if (res.payment?.payment_url) {
      window.location.href = res.payment.payment_url // paiement chez le prestataire
      return
    }
    result.value = res
  } catch (e) {
    payError.value = e.field('amount') || e.field('provider') || e.message
  } finally {
    sending.value = false
  }
}

watch(() => route.params.id, (id) => load(id), { immediate: true })
</script>

<template>
  <div>
  <div class="page-banner"><div class="container"><h1>{{ f?.title || 'Levée de fonds' }}</h1></div></div>
  <section class="section alt">
    <div class="container">
      <p><RouterLink to="/collectes">← Toutes les collectes</RouterLink></p>

      <p v-if="notFound" class="alert err">Cette collecte n'existe pas ou n'est plus affichée.</p>
      <p v-else-if="error" class="alert err">{{ error }}</p>
      <p v-else-if="!f" class="muted">Chargement…</p>

      <div v-else class="fund-layout">
        <!-- Explication : texte, puis image -->
        <article class="card">
          <p v-if="f.summary" style="font-size: 18px; font-weight: 600; margin-top: 0">{{ f.summary }}</p>
          <p v-if="f.description" class="prose">{{ f.description }}</p>
          <p v-if="f.starts_on || f.ends_on" class="muted">
            <template v-if="f.starts_on">Du {{ formatDateShort(f.starts_on) }}</template>
            <template v-if="f.ends_on"> jusqu'au {{ formatDateShort(f.ends_on) }}</template>
          </p>
          <img v-if="f.image_url" :src="f.image_url" :alt="f.title" class="fund-image" />
        </article>

        <!-- Objectif et contribution -->
        <aside class="card fund-aside">
          <div class="kpi-value" style="margin: 0">{{ formatMoney(f.raised_amount ?? 0) }}</div>
          <div class="muted">
            collectés<template v-if="f.goal_amount"> sur {{ formatMoney(f.goal_amount) }}</template>
            · {{ f.contributions_count }} contribution(s)
          </div>
          <div v-if="f.goal_amount" style="margin: 12px 0">
            <div class="progress"><span :style="{ width: percent + '%' }"></span></div>
            <div class="muted" style="margin-top: 4px">{{ percent }} % de l'objectif</div>
          </div>
          <p v-if="f.my_total" class="alert ok">Merci ! Vous avez déjà contribué à hauteur de {{ formatMoney(f.my_total) }}.</p>

          <p v-if="closedReason" class="alert err">{{ closedReason }}</p>

          <template v-else-if="isLoggedIn">
            <div v-if="result" style="margin-top: 12px">
              <p class="alert ok">
                Votre contribution de {{ formatMoney(result.contribution.amount) }} est enregistrée sous la référence
                <b>{{ result.contribution.reference }}</b>.
              </p>
              <p class="muted">
                Le paiement en ligne n'est pas encore activé : rien n'a été débité. Contactez le bureau ({{ site.contact.phone }})
                en rappelant cette référence pour finaliser.
              </p>
            </div>

            <form v-else class="form" @submit.prevent="contribute">
              <p v-if="payError" class="alert err">{{ payError }}</p>
              <div class="field">
                <label for="amount">Votre contribution (FCFA)</label>
                <input id="amount" v-model="form.amount" type="number" min="1" required />
              </div>
              <div class="field">
                <label for="provider">Moyen de paiement</label>
                <select id="provider" v-model="form.provider">
                  <option v-for="[key, label] in providers" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <button class="btn orange" type="submit" :disabled="sending">{{ sending ? 'Envoi…' : 'Contribuer' }}</button>
            </form>
          </template>

          <p v-else class="muted">
            Les contributions sont réservées aux membres.
            <RouterLink :to="{ path: '/connexion', query: { redirect: route.fullPath } }">Connectez-vous</RouterLink>
            ou <RouterLink to="/inscription">devenez membre</RouterLink>.
          </p>
        </aside>
      </div>
    </div>
  </section>
  </div>
</template>