<script setup>
defineProps({ values: { type: Array, required: true } })
</script>

<template>
  <div class="grid">
    <div v-for="(v, i) in values" :key="i" class="card">
      <b>{{ v.title }}</b><br />{{ v.text }}
    </div>
  </div>
</template>