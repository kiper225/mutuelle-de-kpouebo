<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { site } from '../content'

const props = defineProps({ slides: { type: Array, default: () => [] } })

// Sans diapositive (ni en base ni dans content.js), on reconstruit une diapositive à partir de l'ancien bandeau.
const list = computed(() =>
  props.slides.length
    ? props.slides
    : [
        {
          eyebrow: 'UNE SEULE MISSION',
          title: site.slogan,
          text: site.hero,
          image: site.heroImage || '',
          cta: { label: 'Devenir membre', to: '/inscription' },
          cta2: { label: 'Faire un don', to: '/don' },
        },
      ],
)

const index = ref(0)
const paused = ref(false)
let timer = null
let startX = null

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

const go = (i) => (index.value = (i + list.value.length) % list.value.length)
const next = () => go(index.value + 1)
const prev = () => go(index.value - 1)

function stop() {
  clearInterval(timer)
  timer = null
}

function start() {
  stop()
  if (reduceMotion || list.value.length < 2) return
  timer = setInterval(() => {
    if (!paused.value) next()
  }, 6000)
}

function onDown(event) {
  startX = event.clientX
}

function onUp(event) {
  if (startX === null) return
  const dx = event.clientX - startX
  startX = null
  if (Math.abs(dx) > 50) (dx < 0 ? next : prev)()
}

const background = (slide) =>
  slide.image
    ? { backgroundImage: `linear-gradient(90deg, rgba(15, 90, 46, .75), rgba(15, 90, 46, .3)), url(${slide.image})` }
    : {}

// Quand les diapositives changent (chargées depuis le serveur), on repart de la première.
watch(list, () => {
  index.value = 0
  start()
})

onMounted(start)
onBeforeUnmount(stop)
</script>

<template>
  <section
    class="hero-wrap"
    aria-roledescription="carousel"
    aria-label="À la une"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    @focusin="paused = true"
    @focusout="paused = false"
  >
    <div class="carousel" @pointerdown="onDown" @pointerup="onUp">
      <div
        v-for="(s, i) in list"
        :key="i"
        class="slide"
        :class="{ active: i === index }"
        :style="background(s)"
        role="group"
        aria-roledescription="slide"
        :aria-label="`${i + 1} sur ${list.length}`"
      >
        <div class="slide-content">
          <div v-if="s.eyebrow" class="eyebrow">{{ s.eyebrow }}</div>
          <h1 v-if="i === 0">{{ s.title }}</h1>
          <h2 v-else>{{ s.title }}</h2>
          <p v-if="s.text">{{ s.text }}</p>
          <div class="row">
            <RouterLink v-if="s.cta" :to="s.cta.to" class="btn orange">{{ s.cta.label }}</RouterLink>
            <RouterLink v-if="s.cta2" :to="s.cta2.to" class="btn outline">{{ s.cta2.label }}</RouterLink>
          </div>
        </div>
      </div>

      <template v-if="list.length > 1">
        <button class="car-btn prev" type="button" aria-label="Diapositive précédente" @click="prev">‹</button>
        <button class="car-btn next" type="button" aria-label="Diapositive suivante" @click="next">›</button>
        <div class="car-dots">
          <button
            v-for="(s, i) in list"
            :key="i"
            type="button"
            :class="{ active: i === index }"
            :aria-label="`Aller à la diapositive ${i + 1}`"
            :aria-current="i === index"
            @click="go(i)"
          ></button>
        </div>
      </template>
    </div>
  </section>
</template>