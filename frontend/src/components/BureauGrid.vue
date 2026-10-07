<script setup>
import { site } from '../content'
</script>

<template>
  <div class="grid grid-wide">
    <div v-for="m in site.bureau" :key="m.name" class="bureau-card">
      <img v-if="m.photo" :src="m.photo" :alt="`Photo de ${m.name}`" class="bphoto" />
      <div v-else class="bphoto ph">[PHOTO]</div>
      <div class="btext">
        <b>{{ m.name }}</b><br />
        <span class="small-text">{{ m.role }}</span>
        <p class="small-text" style="margin: 12px 0 0">« {{ m.quote }} »</p>
      </div>
    </div>
  </div>
</template>
