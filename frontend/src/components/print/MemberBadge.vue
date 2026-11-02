<script setup>
import { ref, reactive, computed } from 'vue'
import { site } from '../../content'
import { GENDER, formatDateShort } from '../../utils'

const props = defineProps({ member: { type: Object, required: true } })

// Réglages modifiables avant l'impression (valeurs par défaut reprises de content.js)
const issueDate = ref(new Date().toLocaleDateString('fr-FR'))
const signerName = ref(site.signataire.name)
const signerTitle = ref(site.signataire.role)
const signatureText = ref('') // vide : on laisse la place pour une signature à la main

function resetOptions() {
  issueDate.value = new Date().toLocaleDateString('fr-FR')
  signerName.value = site.signataire.name
  signerTitle.value = site.signataire.role
  signatureText.value = ''
}

const joinedYear = computed(() => (props.member.joined_at ? String(props.member.joined_at).slice(0, 4) : ''))

const rows = computed(() => [
  ['Nom', props.member.last_name],
  ['Prénoms', props.member.first_names],
  ['Sexe', GENDER[props.member.gender] || ''],
  ['Né(e) le', formatDateShort(props.member.birth_date)],
  ['N° membre', props.member.member_number || ''],
  ['Fonction', props.member.mutuelle_role || ''],
  ['Adresse', props.member.residence || ''],
])

/* Effet 3D au survol (écran uniquement) */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
const stage = ref(null)
const tilt = reactive({ on: false, x: 0, y: 0, gx: 50, gy: 50 })

function onMove(event) {
  if (reduceMotion || !stage.value) return
  const r = stage.value.getBoundingClientRect()
  const x = event.clientX - r.left
  const y = event.clientY - r.top
  tilt.on = true
  tilt.x = -((y - r.height / 2) / (r.height / 2)) * 10
  tilt.y = ((x - r.width / 2) / (r.width / 2)) * 10
  tilt.gx = (x / r.width) * 100
  tilt.gy = (y / r.height) * 100
}

function onLeave() {
  tilt.on = false
  tilt.x = 0
  tilt.y = 0
}

const cardStyle = computed(() =>
  tilt.on
    ? { transform: `rotateX(${tilt.x}deg) rotateY(${tilt.y}deg) scale(1.02)` }
    : { transform: 'none', transition: 'transform .5s ease' },
)
const glareStyle = computed(() => ({
  background: `radial-gradient(circle at ${tilt.gx}% ${tilt.gy}%, rgba(255, 255, 255, .4) 0%, rgba(255, 255, 255, 0) 70%)`,
}))
</script>

<template>
  <section class="doc-page">
    <div class="doc-title">CARTE DE MEMBRE</div>

    <!-- Personnalisation (non imprimée) -->
    <div class="card no-print bd-edit">
      <h3 style="margin: 0 0 12px; font-size: 16px; color: var(--green)">Personnaliser avant l'impression</h3>
      <div class="row">
        <div class="field">
          <label for="bd-date">Date d'émission</label>
          <input id="bd-date" v-model="issueDate" type="text" />
        </div>
        <div class="field">
          <label for="bd-signer">Signataire (nom)</label>
          <input id="bd-signer" v-model="signerName" type="text" />
        </div>
        <div class="fiel">
          <label for="bd-title">Titre du signataire</label>
          <input id="bd-title" v-model="signerTitle" type="text" />
        </div>
      </div>
      <div class="row" style="margin-top: 12px; align-items: flex-end">
        <div class="field">
          <label for="bd-sign">Signature écrite (facultative)</label>
          <input id="bd-sign" v-model="signatureText" type="text" placeholder="Laissez vide pour signer à la main" />
        </div>
        <button type="button" class="btn outline-green small" @click="resetOptions">Réinitialiser</button>
      </div>
      <p class="muted" style="margin: 12px 0 0">
        Imprimez à 100 % (sans mise à l'échelle) : chaque carte fait 85,6 × 54 mm, le format d'une carte bancaire.
        Découpez le long du trait. Recto à gauche, verso à droite. Survolez le recto pour voir l'effet 3D.
      </p>
    </div>

    <div class="bd-wrap">
      <!-- Recto -->
      <div ref="stage" class="bd-stage" @mousemove="onMove" @mouseleave="onLeave">
        <div class="bd-card" :style="cardStyle">
          <div class="bd-glare" :class="{ on: tilt.on }" :style="glareStyle"></div>
          <div class="bd-stripe-orange"></div>
          <div class="bd-stripe-green"></div>

          <div class="bd-content">
            <div class="bd-head">
              <img src="/logo.jpg" alt="" class="bd-logo" />
              <div class="bd-name">{{ site.name }}</div>
            </div>

            <div>
              <div class="bd-band">Carte de membre</div>

              <div class="bd-main">
                <div class="bd-info">
                  <div v-for="[label, value] in rows" :key="label" class="bd-row" :class="{ wrap: label === 'Adresse' }">
                    <span class="bd-label">{{ label }}</span>
                    <span class="bd-value">: {{ value }}</span>
                  </div>
                </div>

                <div class="bd-side">
                  <img v-if="member.photo_url" :src="member.photo_url" alt="Photo du membre" class="bd-photo" />
                  <div v-else class="bd-photo empty">Photo</div>
                </div>
              </div>
            </div>

            <div class="bd-foot">
              <div class="bd-date">Date d'émission : <b>{{ issueDate }}</b></div>
              <p class="bd-notice">{{ site.badgeNotice }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Verso -->
      <div class="bd-stage">
        <div class="bd-card">
          <div class="bd-stripe-orange"></div>
          <div class="bd-stripe-green"></div>

          <div class="bd-content">
            <div class="bd-back rise">
              <div class="bd-big">N° {{ member.member_number }}</div>
              <div v-if="joinedYear" class="bd-since">Membre depuis {{ joinedYear }}</div>
              <div class="bd-contact">
                {{ site.contact.phone }} · {{ site.contact.email }}<br />
                {{ site.contact.address }}
              </div>
              <div class="bd-contact">
                Signataire : <b>{{ signerName }}</b>, {{ signerTitle }}
              </div>
            </div>

            <div class="bd-foot">
              <p class="bd-notice" style="margin-top: 0">{{ site.badgeNotice }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>