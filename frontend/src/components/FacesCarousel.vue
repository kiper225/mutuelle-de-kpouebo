<script setup>
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({ items: { type: Array, required: true } })

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
  () => props.items.length,
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
      <div v-for="(f, i) in items" :key="i" class="face-slide">
        <article class="face-card">
          <div class="face-photo">
            <img v-if="f.photo" :src="f.photo" :alt="`Portrait de ${f.name}`" loading="lazy" />
            <span v-else class="face-initial" aria-hidden="true">{{ initial(f.name) }}</span>
          </div>
          <div class="face-body">
            <h3>{{ f.name }}</h3>
            <div class="face-role">{{ f.role }}</div>
            <p v-if="f.text">{{ f.text }}</p>
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