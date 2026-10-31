<script setup>
import AxisIcon from './AxisIcon.vue'

defineProps({ values: { type: Array, required: true } })
</script>

<template>
  <div class="hidden md:flex flex-wrap justify-center gap-8 max-w-6xl mx-auto values-grid">
    <div v-for="(v, i) in values" :key="i" class="w-[300px] group p-6 border border-gray-200 hover:border-green-600 rounded-2xl shadow hover:shadow-lg transition duration-300">
      <div class="w-16 h-16 mx-auto flex items-center justify-center rounded-full bg-green-50 group-hover:bg-green-600 transition duration-300">
        <img v-if="v.image" :src="v.image" :alt="v.title" class="w-10 h-10 object-contain item-media" loading="lazy" />
        <AxisIcon v-else-if="v.icon" :name="v.icon" class="w-10 h-10 text-green-600 group-hover:text-white transition duration-300 item-media" />
      </div>
      <h3 class="text-lg font-semibold mt-4 text-gray-800 group-hover:text-green-600 text-center pt-2">{{ v.title }}</h3>
      <p class="text-sm text-gray-500 mt-2 text-center">{{ v.text }}</p>
    </div>
  </div>
</template>