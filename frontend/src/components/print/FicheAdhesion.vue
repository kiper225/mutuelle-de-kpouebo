<script setup>
import { ref, computed, watch } from 'vue'
import QRCode from 'qrcode'
import { site } from '../../content'
import { GENDER, MARITAL_STATUS, formatDateShort } from '../../utils'

const props = defineProps({ member: { type: Object, required: true } })

// Remplit la déclaration avec les informations du membre (sinon : lignes à compléter à la main).
const prefill = ref(false)

const now = new Date()
const printedAt = `${now.toLocaleDateString('fr-FR')} à ${now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`

const fullName = computed(() => [props.member.last_name, props.member.first_names].filter(Boolean).join(' '))
const matricule = computed(() => props.member.member_number || 'NON ATTRIBUÉ')
const birthDate = computed(() => formatDateShort(props.member.birth_date))

const show = (value) => (value === null || value === undefined || value === '' ? '—' : String(value))

const personal = computed(() => {
  const m = props.member
  return [
    { label: 'Nom', value: m.last_name },
    { label: 'Prénoms', value: m.first_names },
    { label: 'Sexe', value: GENDER[m.gender] },
    { label: 'Né(e) le', value: m.birth_date ? `${birthDate.value}${m.birth_place ? ` à ${m.birth_place}` : ''}` : '' },
    { label: 'Nom du père', value: m.father_name },
    { label: 'Nom de la mère', value: m.mother_name },
    { label: 'Situation matrimoniale', value: MARITAL_STATUS[m.marital_status] },
    { label: "Nombre d'enfants", value: m.children_count },
    { label: 'Téléphone', value: m.phone },
    { label: 'Email', value: m.email, lower: true },
    { label: 'Habite', value: m.residence },
  ]
})

const professional = computed(() => {
  const m = props.member
  return [
    { label: 'Profession', value: m.profession },
    { label: 'Poste au sein de la mutuelle', value: m.mutuelle_role },
    { label: "Date d'adhésion", value: m.joined_at ? formatDateShort(m.joined_at) : 'En attente de validation' },
  ]
})

const adhesionText = computed(() => (site.declaration?.adhesion || '').replace('{association}', site.name))
const statutsText = computed(() => site.declaration?.statuts || '')

// QR code : nom de la mutuelle, matricule et nom du membre.
const qr = ref('')
watch(
  () => [props.member.id, props.member.member_number],
  async () => {
    const text = [site.name, props.member.member_number || `Réf. ${props.member.id}`, fullName.value].join('\n')
    try {
      qr.value = await QRCode.toDataURL(text, { margin: 0, width: 260, color: { dark: '#17753f', light: '#ffffff' } })
    } catch {
      qr.value = ''
    }
  },
  { immediate: true },
)
</script>

<template>
  <div>
    <!-- Options (non imprimées) -->
    <div class="card no-print fa-edit">
      <label class="check">
        <input v-model="prefill" type="checkbox" />
        <span>
          Pré-remplir la déclaration (2ème partie) avec les informations du membre.
          Décoché : les lignes pointillées restent à compléter à la main.
        </span>
      </label>
      <p class="muted" style="margin: 8px 0 0">
        La fiche compte 2 pages A4. Imprimez à 100 % (sans mise à l'échelle). La 2ème partie est à faire signer par le membre,
        puis à conserver par l'association.
      </p>
      <p v-if="!site.declaration" class="alert err" style="margin: 8px 0 0">
        Le texte de la déclaration est absent : ajoutez la clé <code>declaration</code> dans <code>src/content.js</code>.
      </p>
    </div>

    <!-- Page 1 : fiche -->
    <section class="fa-page">
      <header class="fa-head">
        <img src="/logo.jpg" alt="" class="fa-logo" />
        <div class="fa-org">
          <h1>{{ site.name }}</h1>
          <p>E-mail : {{ site.contact.email }}</p>
          <p>Contact : {{ site.contact.phone }}</p>
          <p v-if="site.contact.address">{{ site.contact.address }}</p>
        </div>
        <img src="/logo.jpg" alt="" class="fa-logo" />
      </header>

      <div class="fa-meta">
        <span><b>Date d'impression</b> : {{ printedAt }}</span>
        <span><b>Réf.</b> : {{ member.id }}</span>
      </div>

      <div class="fa-banner">Fiche d'adhésion</div>
      <h2 class="fa-part">1ère partie <small>(à conserver par le membre)</small></h2>

      <div class="fa-id">
        <img v-if="qr" :src="qr" alt="" class="fa-qr" />
        <span v-else></span>
        <div class="fa-matricule">MATRICULE : {{ matricule }}</div>
        <img v-if="member.photo_url" :src="member.photo_url" alt="Photo du membre" class="fa-photo" />
        <div v-else class="fa-photo empty">Photo</div>
      </div>

      <h2 class="fa-section">Informations personnelles</h2>
      <dl class="fa-list">
        <template v-for="row in personal" :key="row.label">
          <dt>{{ row.label }} :</dt>
          <dd :class="{ lower: row.lower }">{{ show(row.value) }}</dd>
        </template>
      </dl>

      <h2 class="fa-section">Informations professionnelles</h2>
      <dl class="fa-list">
        <template v-for="row in professional" :key="row.label">
          <dt>{{ row.label }} :</dt>
          <dd>{{ show(row.value) }}</dd>
        </template>
      </dl>

      <div class="fa-sign">
        <div class="fa-sign-box">
          <b>Visa du bureau</b>
          <span>{{ site.signataire.name }}, {{ site.signataire.role }}</span>
        </div>
      </div>
    </section>

    <!-- Page 2 : déclaration, à conserver par l'association -->
    <section class="fa-page">
      <header class="fa-head">
        <img src="/logo.jpg" alt="" class="fa-logo" />
        <div class="fa-org">
          <h1>{{ site.name }}</h1>
          <p>E-mail : {{ site.contact.email }}</p>
          <p>Contact : {{ site.contact.phone }}</p>
          <p v-if="site.contact.address">{{ site.contact.address }}</p>
        </div>
        <img src="/logo.jpg" alt="" class="fa-logo" />
      </header>

      <div class="fa-meta">
        <span><b>Matricule</b> : {{ matricule }}</span>
        <span><b>Réf.</b> : {{ member.id }}</span>
      </div>

      <div class="fa-banner">Fiche d'adhésion</div>

      <h2 class="fa-part">2ème partie <small>(à conserver par l'association)</small></h2>

      <p class="fa-decl">
        Je soussigné M/Mme/Mlle <span class="fa-fill wide">{{ prefill ? fullName : '' }}</span>
        Née le <span class="fa-fill">{{ prefill ? birthDate : '' }}</span>
        À <span class="fa-fill">{{ prefill ? member.birth_place : '' }}</span>
        Demeurant à <span class="fa-fill wide">{{ prefill ? member.residence : '' }}</span>
      </p>
      <p class="fa-decl">{{ adhesionText }}</p>
      <p class="fa-decl">{{ statutsText }}</p>

      <p class="fa-decl fa-fait">Fait à <span class="fa-fill"></span>, le <span class="fa-fill"></span></p>

      <div class="fa-sign">
        <div class="fa-sign-box"><b>Signature du membre</b></div>
      </div>
    </section>
  </div>
</template>