<script setup>
import { site } from '../content'
const p = site.president
</script>

<template>
  <div id="president" class="card president">
    <img v-if="p.photo" :src="p.photo" :alt="`Photo de ${p.name}`" class="photo" />
    <div v-else class="photo ph">[PHOTO DU PRÉSIDENT]</div>
    <div class="text">
      <div class="eyebrow-green">Mot du Président</div>
      <p class="prose">{{ p.message }}</p>
      <b>{{ p.name }}</b><br />
      <span class="muted">{{ p.role }}</span>
    </div>
  </div>
</template>
