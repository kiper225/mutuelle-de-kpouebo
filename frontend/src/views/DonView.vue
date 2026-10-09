<script setup>
import { reactive, ref } from 'vue'
import { api } from '../api'
import { auth } from '../auth'
import { site } from '../content'
import { PROVIDERS, formatMoney } from '../utils'

const providers = Object.entries(PROVIDERS).filter(([key]) => key !== 'cash')

const form = reactive({
  donor_name: auth.user?.name || '',
  donor_phone: auth.user?.phone || '',
  donor_email: '',
  amount: '',
  provider: 'orange_money',
  message: '',
})
const errors = ref({})
const error = ref('')
const result = ref(null)
const sending = ref(false)

const err = (name) => errors.value[name]?.[0] || ''

async function submit() {
  sending.value = true
  errors.value = {}
  error.value = ''
  try {
    const res = await api.post('/donations', {
      donor_name: form.donor_name,
      donor_phone: form.donor_phone,
      donor_email: form.donor_email,
      amount: Number(form.amount),
      provider: form.provider,
      message: form.message,
    })
    if (res.payment?.payment_url) {
      window.location.href = res.payment.payment_url // paiement chez le prestataire
      return
    }
    result.value = res
  } catch (e) {
    errors.value = e.errors
    error.value = Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="don-view">
    <div class="page-banner"><div class="container"><h1>Faire un don</h1></div></div>
    <section class="section alt">
      <div class="container narrow">
        <div v-if="result" class="card">
          <h2 style="color: var(--green)">Merci pour votre générosité</h2>
          <p class="alert ok">
            Votre don de {{ formatMoney(result.donation.amount) }} est enregistré sous la référence
            <b>{{ result.donation.reference }}</b>.
          </p>
          <p>
            Le paiement en ligne n'est pas encore activé : il n'a donc pas été débité.
            Pour le finaliser, suivez les indications ci-dessous en rappelant votre référence.
          </p>
          <p class="prose">{{ site.don.howTo }}</p>
          <RouterLink to="/" class="btn green">Retour à l'accueil</RouterLink>
        </div>

        <form v-else class="card form" @submit.prevent="submit">
          <h2 style="color: var(--green)">Soutenez les actions de la mutuelle</h2>
          <p class="prose">{{ site.don.intro }}</p>
          <p v-if="error" class="alert err">{{ error }}</p>

          <div class="row">
            <div class="field">
              <label for="amount">Montant du don (FCFA)</label>
              <input id="amount" v-model="form.amount" type="number" min="1" required />
              <span class="error">{{ err('amount') }}</span>
            </div>
            <div class="field">
              <label for="provider">Moyen de paiement</label>
              <select id="provider" v-model="form.provider">
                <option v-for="[key, label] in providers" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="field">
              <label for="name">Votre nom</label>
              <input id="name" v-model="form.donor_name" type="text" required />
              <span class="error">{{ err('donor_name') }}</span>
            </div>
            <div class="field">
              <label for="phone">Téléphone</label>
              <input id="phone" v-model="form.donor_phone" type="tel" placeholder="+225…" />
              <span class="error">{{ err('donor_phone') }}</span>
            </div>
          </div>
          <div class="field">
            <label for="email">Email (facultatif)</label>
            <input id="email" v-model="form.donor_email" type="email" />
            <span class="error">{{ err('donor_email') }}</span>
          </div>
          <div class="field">
            <label for="message">Message (facultatif)</label>
            <textarea id="message" v-model="form.message" rows="3" maxlength="500"></textarea>
          </div>
          <button class="btn orange" type="submit" :disabled="sending" style="align-self: flex-start">
            {{ sending ? 'Envoi…' : 'Faire mon don' }}
          </button>
        </form>
      </div>
    </section>
  </div>
</template>