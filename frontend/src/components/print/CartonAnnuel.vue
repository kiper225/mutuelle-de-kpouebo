<script setup>
import { site } from '../../content'
import { MONTHS } from '../../utils'

defineProps({
  member: { type: Object, required: true },
  year: { type: Number, required: true },
})
</script>

<template>
  <section class="doc-page">
    <header class="doc-head">
      <img src="/logo.jpg" alt="Logo" />
      <div>
        <h1>{{ site.name }}</h1>
        <div>{{ site.contact.address }}</div>
      </div>
    </header>

    <div class="doc-title">CARTON ANNUEL DE COTISATION {{ year }}</div>

    <div class="doc-top">
      <table class="doc-table">
        <tbody>
          <tr><td class="label">Nom</td><td>{{ member.last_name }}</td></tr>
          <tr><td class="label">Prénoms</td><td>{{ member.first_names }}</td></tr>
          <tr><td class="label">N° de membre</td><td>{{ member.member_number }}</td></tr>
          <tr><td class="label">Poste</td><td>{{ member.mutuelle_role }}</td></tr>
        </tbody>
      </table>
      <img v-if="member.photo_url" :src="member.photo_url" class="doc-photo" alt="Photo du membre" />
      <div v-else class="doc-photo empty">Photo</div>
    </div>

    <table class="doc-table month-table">
      <thead>
        <tr><th>Mois</th><th class="center">Payé</th><th>Date</th><th>Montant (FCFA)</th><th>Visa</th></tr>
      </thead>
      <tbody>
        <tr v-for="m in MONTHS" :key="m">
          <td class="label">{{ m }}</td>
          <td class="center"><span class="cb"></span></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      </tbody>
    </table>

    <div class="sign-row">
      <div class="sign-box">Total annuel : ______________ FCFA</div>
      <div class="sign-box">
        <b>Le membre du bureau désigné</b><br />
        {{ site.signataire.name }}<br />
        {{ site.signataire.role }}<br /><br />
        Signature et cachet
      </div>
    </div>
  </section>
</template>