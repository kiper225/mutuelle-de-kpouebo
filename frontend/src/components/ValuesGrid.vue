<script setup>
import { site } from '../content'
</script>

<template>
  <div class="grid">
    <div v-for="v in site.values" :key="v.title" class="card center">
      <div class="center" style="font-size: 60px;">{{ v.icon }}</div>
      <b style="color: var(--green);">{{ v.title }}</b><br />{{ v.text }} 
    </div>
  </div>
</template>
