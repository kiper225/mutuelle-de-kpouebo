<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  file: { type: File, required: true },
  aspect: { type: Number, default: 3 / 4 }, // largeur / hauteur du cadre
  outputWidth: { type: Number, default: 600 },
  title: { type: String, default: 'Recadrer la photo' },
})
const emit = defineEmits(['done', 'cancel'])

const url = URL.createObjectURL(props.file)
const ready = ref(false)
const loadError = ref('')

const vw = ref(0) // taille du cadre à l'écran (px)
const vh = ref(0)
const iw = ref(0) // taille réelle de l'image (px)
const ih = ref(0)
const scale = ref(1)
const minScale = ref(1)
const maxScale = ref(4)
const ox = ref(0) // position de l'image dans le cadre
const oy = ref(0)

let image = null
let drag = null

const imgStyle = computed(() => ({
  position: 'absolute',
  left: '0',
  top: '0',
  width: `${iw.value * scale.value}px`,
  height: `${ih.value * scale.value}px`,
  maxWidth: 'none',
  transform: `translate(${ox.value}px, ${oy.value}px)`,
  userSelect: 'none',
  pointerEvents: 'none',
}))

/** L'image doit toujours couvrir entièrement le cadre. */
function clamp() {
  ox.value = Math.min(0, Math.max(vw.value - iw.value * scale.value, ox.value))
  oy.value = Math.min(0, Math.max(vh.value - ih.value * scale.value, oy.value))
}

/** Zoom centré sur le milieu du cadre. */
function setScale(next) {
  const s = Math.min(maxScale.value, Math.max(minScale.value, next))
  const cx = (vw.value / 2 - ox.value) / scale.value
  const cy = (vh.value / 2 - oy.value) / scale.value
  scale.value = s
  ox.value = vw.value / 2 - cx * s
  oy.value = vh.value / 2 - cy * s
  clamp()
}

function onDown(event) {
  drag = { x: event.clientX, y: event.clientY }
  event.currentTarget.setPointerCapture(event.pointerId)
}

function onMove(event) {
  if (!drag) return
  ox.value += event.clientX - drag.x
  oy.value += event.clientY - drag.y
  drag = { x: event.clientX, y: event.clientY }
  clamp()
}

function onUp() {
  drag = null
}

function onWheel(event) {
  setScale(scale.value * (event.deltaY < 0 ? 1.08 : 0.92))
}

function onKey(event) {
  const step = 10
  if (event.key === 'ArrowLeft') ox.value += step
  else if (event.key === 'ArrowRight') ox.value -= step
  else if (event.key === 'ArrowUp') oy.value += step
  else if (event.key === 'ArrowDown') oy.value -= step
  else if (event.key === '+' || event.key === '=') setScale(scale.value * 1.1)
  else if (event.key === '-') setScale(scale.value / 1.1)
  else return
  event.preventDefault()
  clamp()
}

function confirm() {
  // Zone de l'image d'origine visible dans le cadre
  const sw = vw.value / scale.value
  const sh = vh.value / scale.value
  const sx = -ox.value / scale.value
  const sy = -oy.value / scale.value

  const outW = Math.min(props.outputWidth, Math.round(sw)) // pas d'agrandissement artificiel
  const outH = Math.round(outW / props.aspect)

  const canvas = document.createElement('canvas')
  canvas.width = outW
  canvas.height = outH
  const ctx = canvas.getContext('2d')
  ctx.fillStyle = '#fff' // fond blanc pour les PNG transparents
  ctx.fillRect(0, 0, outW, outH)
  ctx.drawImage(image, sx, sy, sw, sh, 0, 0, outW, outH)

  canvas.toBlob(
    (blob) => {
      if (!blob) {
        loadError.value = "Impossible de préparer l'image."
        return
      }
      emit('done', new File([blob], 'photo.jpg', { type: 'image/jpeg' }))
    },
    'image/jpeg',
    0.9,
  )
}

onMounted(() => {
  const maxW = Math.min(window.innerWidth - 80, 480)
  const maxH = Math.min(window.innerHeight * 0.55, 380)
  vw.value = Math.round(Math.min(maxW, maxH * props.aspect))
  vh.value = Math.round(vw.value / props.aspect)

  const im = new Image()
  im.onload = () => {
    image = im
    iw.value = im.naturalWidth
    ih.value = im.naturalHeight
    minScale.value = Math.max(vw.value / iw.value, vh.value / ih.value)
    maxScale.value = minScale.value * 4
    scale.value = minScale.value
    ox.value = (vw.value - iw.value * scale.value) / 2
    oy.value = (vh.value - ih.value * scale.value) / 2
    ready.value = true
  }
  im.onerror = () => {
    loadError.value = "Ce fichier n'est pas une image lisible."
  }
  im.src = url
})

onBeforeUnmount(() => URL.revokeObjectURL(url))
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('cancel')">
    <div class="modal" role="dialog" aria-modal="true" :aria-label="title">
      <h3>{{ title }}</h3>
      <p class="muted">Faites glisser l'image pour la cadrer, puis ajustez le zoom.</p>

      <p v-if="loadError" class="alert err">{{ loadError }}</p>
      <p v-else-if="!ready" class="muted">Chargement…</p>

      <template v-else>
        <div
          class="crop-frame"
          :style="{ width: vw + 'px', height: vh + 'px' }"
          tabindex="0"
          @pointerdown="onDown"
          @pointermove="onMove"
          @pointerup="onUp"
          @pointercancel="onUp"
          @wheel.prevent="onWheel"
          @keydown="onKey"
        >
          <img :src="url" alt="" draggable="false" :style="imgStyle" />
        </div>
        <label class="zoom">
          Zoom
          <input
            type="range"
            :min="minScale"
            :max="maxScale"
            step="any"
            :value="scale"
            @input="setScale(Number($event.target.value))"
          />
        </label>
      </template>

      <div class="actions" style="justify-content: center">
        <button class="btn green" type="button" :disabled="!ready" @click="confirm">Valider le cadrage</button>
        <button class="btn outline-green" type="button" @click="emit('cancel')">Annuler</button>
      </div>
    </div>
  </div>
</template>