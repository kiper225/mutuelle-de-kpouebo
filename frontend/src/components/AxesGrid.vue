<script setup>
import AxisIcon from './AxisIcon.vue'

import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({ axes: { type: Array, required: true } })

const track = ref(null)
const canPrev = ref(false)
const canNext = ref(false)
const paused = ref(false)
 
let timer = null
let observer = null

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

// Les noms encore entre crochets (modèle) donnent l'initiale de la lettre qui suit le crochet.
const initial = (name) => String(name || '?').replace(/^\[/, '').trim().charAt(0).toUpperCase()

/** Largeur d'une carte + l'espace entre deux cartes. */
function step() {
  const el = track.value
  const slide = el?.querySelector('.face-slide')
  if (!el || !slide) return 0
  return slide.getBoundingClientRect().width + (parseFloat(getComputedStyle(el).columnGap) || 0)
}

function update() {
  const el = track.value
  if (!el) return
  canPrev.value = el.scrollLeft > 4
  canNext.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 4
}

function next() {
  const el = track.value
  if (!el) return
  if (canNext.value) el.scrollBy({ left: step(), behavior: 'smooth' })
  else el.scrollTo({ left: 0, behavior: 'smooth' }) // retour au début
}

function prev() {
  const el = track.value
  if (!el) return
  if (canPrev.value) el.scrollBy({ left: -step(), behavior: 'smooth' })
  else el.scrollTo({ left: el.scrollWidth, behavior: 'smooth' }) // saut à la fin
}

function stop() {
  clearInterval(timer)
  timer = null
}

function start() {
  stop()
  if (reduceMotion) return
  timer = setInterval(() => {
    if (!paused.value && (canPrev.value || canNext.value)) next()
  }, 5000)
}

watch(
  () => props.axes.length,
  async () => {
    await nextTick()
    update()
  },
)

onMounted(() => {
  update()
  start()
  observer = new ResizeObserver(update) // recalcul quand la fenêtre change de taille
  if (track.value) observer.observe(track.value)
})

onBeforeUnmount(() => {
  stop()
  observer?.disconnect()
})
</script>

<template>
  <div
    class="news-carousel"
    aria-roledescription="carousel"
    aria-label="Visages emblématiques de Kpouèbo"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    @focusin="paused = true"
    @focusout="paused = false"
    @touchstart.passive="paused = true"
    @touchend.passive="paused = false"
  >
    <div ref="track" class="news-track" tabindex="0" @scroll.passive="update">
      <div v-for="(s, i) in axes" :key="i" class="w-full md:w-1/3 bg-transparent snap-start shrink-0">
        <article class="flex flex-col items-center text-center bg-transparent pt-12 pb-6 relative">
          <div class="w-16 h-16 mb-[-2rem] z-10">
            <img v-if="s.image" :src="s.image" :alt="s.title" class="w-full h-full object-contain bg-white rounded-full shadow-lg flex items-center justify-center p-2 item-media" loading="lazy" />
            <AxisIcon v-else-if="s.icon" :name="s.icon" class="w-full h-full object-contain bg-white rounded-full shadow-lg flex items-center justify-center p-2 item-media" />
          </div>
          <div class="mt-4 pt-4 min-h-[150px] bg-white rounded-lg shadow-md px-4 pb-6">
            <h3 class="center text-green-700 font-semibold text-lg pt-5">{{ s.title }}</h3>
            <p v-if="s.text" class="text-gray-600 text-sm mt-2">{{ s.text }}</p>
          </div>
        </article>
      </div>
    </div>
    

    <template v-if="canPrev || canNext">
      <button class="news-btn prev" type="button" aria-label="Visages précédents" @click="prev">‹</button>
      <button class="news-btn next" type="button" aria-label="Visages suivants" @click="next">›</button>
    </template>
  </div>
</template>

