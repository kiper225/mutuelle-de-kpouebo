<script setup>
import { computed } from 'vue'
import { site } from '../../content'
import { formatDateShort } from '../../utils'

const props = defineProps({ member: { type: Object, required: true } })
const joinedYear = computed(() => (props.member.joined_at ? String(props.member.joined_at).slice(0, 4) : ''))
</script>

<template>
  <section class="doc-page">
    <div class="doc-title">CARTE DE MEMBRE</div>
    <p class="no-print muted" style="text-align: center">
      Imprimez à 100 % (sans mise à l'échelle) : chaque carte fait 85,6 × 54 mm, le format d'une carte bancaire.
      Découpez le long du trait. Recto à gauche, verso à droite.
    </p>

    <div class="badge-wrap">
      <!-- Recto -->
      <div class="cr80">
        <div class="cr-band">
          <img src="/logo.jpg" class="cr-logo" alt="" />
          <span>{{ site.name }}</span>
        </div>
        <div class="cr-body">
          <img v-if="member.photo_url" :src="member.photo_url" class="cr-photo" alt="Photo du membre" />
          <div v-else class="cr-photo empty">Photo</div>
          <div>
            <div class="cr-name">{{ member.last_name }}</div>
            <div>{{ member.first_names }}</div>
            <div class="cr-line"><b>N° {{ member.member_number }}</b></div>
            <div class="cr-line">{{ member.mutuelle_role }}</div>
            <div class="cr-line">Né(e) le {{ formatDateShort(member.birth_date) }}</div>
          </div>
        </div>
      </div>

      <!-- Verso -->
      <div class="cr80">
        <div class="cr-body vertical">
          <div><b>Membre depuis {{ joinedYear }}</b></div>
          <div class="cr-notice">{{ site.badgeNotice }}</div>
          <div class="cr-notice">
            Le membre du bureau désigné :<br />
            <b>{{ site.signataire.name }}</b>, {{ site.signataire.role }}
            <div class="cr-sign-box"></div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>