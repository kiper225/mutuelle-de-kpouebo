<script setup>
import { computed } from 'vue'
import { AXIS_ICONS } from '../axisIcons'

const props = defineProps({ name: { type: String, default: '' } })

// Le dessin vient uniquement de la liste ci-dessus (jamais de texte saisi par un utilisateur).
const svg = computed(() => AXIS_ICONS[props.name]?.svg || '')
</script>

<template>
  <svg
    v-if="svg"
    viewBox="0 0 24 24" width="52" height="52" fill="none" stroke="currentColor"
    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
    v-html="svg"
  ></svg>
</template>