<script setup>
import { site } from '../content'
const embeddable = typeof site.videoUrl === 'string' && site.videoUrl.startsWith('https://')
</script>

<template>
  <div class="video ph">
    <iframe
      v-if="embeddable"
      :src="site.videoUrl"
      title="Vidéo de présentation de la mutuelle"
      allow="accelerometer; encrypted-media; picture-in-picture"
      allowfullscreen
      loading="lazy"
    ></iframe>
    <template v-else>
      <div class="play" aria-hidden="true">▶</div>
      <span class="video-label">[Vidéo de présentation]</span>
    </template>
  </div>
</template>
