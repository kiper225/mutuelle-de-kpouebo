<script setup>
defineProps({ page: { type: Number, required: true }, last: { type: Number, required: true } })
defineEmits(['change'])
</script>

<template>
  <div v-if="last > 1" class="pager">
    <button class="btn outline-green small" :disabled="page <= 1" @click="$emit('change', page - 1)">Précédent</button>
    <span class="muted">Page {{ page }} sur {{ last }}</span>
    <button class="btn outline-green small" :disabled="page >= last" @click="$emit('change', page + 1)">Suivant</button>
  </div>
</template>
