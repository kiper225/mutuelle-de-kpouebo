<script setup>
import { site } from '../../content'
import { MARITAL_STATUS, formatDateShort } from '../../utils'

defineProps({ member: { type: Object, required: true } })
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

    <div class="doc-title">FICHE D'ADHÉSION</div>

    <div class="doc-top">
      <table class="doc-table">
        <tbody>
          <tr><td class="label">N° de membre</td><td>{{ member.member_number }}</td></tr>
          <tr><td class="label">Date d'adhésion</td><td>{{ formatDateShort(member.joined_at) }}</td></tr>
        </tbody>
      </table>
      <img v-if="member.photo_url" :src="member.photo_url" class="doc-photo" alt="Photo du membre" />
      <div v-else class="doc-photo empty">Photo</div>
    </div>

    <div class="doc-section">ÉTAT CIVIL</div>
    <table class="doc-table">
      <tbody>
        <tr><td class="label">Nom</td><td>{{ member.last_name }}</td></tr>
        <tr><td class="label">Prénoms</td><td>{{ member.first_names }}</td></tr>
        <tr><td class="label">Date de naissance</td><td>{{ formatDateShort(member.birth_date) }}</td></tr>
        <tr><td class="label">Lieu de naissance</td><td>{{ member.birth_place }}</td></tr>
        <tr><td class="label">Nombre d'enfants</td><td>{{ member.children_count }}</td></tr>
      </tbody>
    </table>

    <div class="doc-section">INFORMATIONS PERSONNELLES</div>
    <table class="doc-table">
      <tbody>
        <tr><td class="label">Statut matrimonial</td><td>{{ MARITAL_STATUS[member.marital_status] || '' }}</td></tr>
        <tr><td class="label">Profession</td><td>{{ member.profession }}</td></tr>
        <tr><td class="label">Lieu de résidence</td><td>{{ member.residence }}</td></tr>
        <tr><td class="label">Poste au sein de la mutuelle</td><td>{{ member.mutuelle_role }}</td></tr>
        <tr><td class="label">Téléphone</td><td>{{ member.phone }}</td></tr>
        <tr><td class="label">Email</td><td>{{ member.email }}</td></tr>
      </tbody>
    </table>

    <p class="doc-text">{{ site.engagement }}</p>

    <div class="sign-row">
      <div class="sign-box">
        Fait à ____________________, le ____ / ____ / ________<br /><br />
        <b>Signature du membre</b>
      </div>
      <div class="sign-box">
        <b>Le membre du bureau désigné</b><br />
        {{ site.signataire.name }}<br />
        {{ site.signataire.role }}<br /><br />
        Signature et cachet
      </div>
    </div>
  </section>
</template>