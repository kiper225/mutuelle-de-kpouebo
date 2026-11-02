<script setup>
defineProps({
  name: { type: String, default: '' },
  photo: { type: String, default: '' },
  online: { type: Boolean, default: false },
  size: { type: Number, default: 42 },
})

const initial = (name) => String(name || '?').trim().charAt(0).toUpperCase()
</script>

<template>
  <span class="pavatar" :style="{ '--s': size + 'px' }">
    <img v-if="photo" :src="photo" alt="" class="avatar" />
    <span v-else class="avatar">{{ initial(name) }}</span>
    <span v-if="online" class="presence-dot" role="img" aria-label="En ligne" title="En ligne"></span>
  </span>
</template>