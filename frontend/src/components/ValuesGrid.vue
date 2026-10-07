<script setup>
import { site } from '../content'
</script>

<template>
  <div class="grid">
    <div v-for="v in site.values" :key="v.title" class="card">
      <b>{{ v.title }}</b><br />{{ v.text }}
    </div>
  </div>
</template>
