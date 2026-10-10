<script setup>
import { computed } from 'vue'

const props = defineProps({
  labels: { type: Array, required: true }, // mois au format « 2026-10 »
  values: { type: Array, required: true },
  color: { type: String, default: '#17753f' },
  formatValue: { type: Function, default: (v) => String(v) },
})

const W = 560
const H = 230
const PAD_L = 46
const PAD_R = 8
const PAD_T = 12
const PAD_B = 28
const plotW = W - PAD_L - PAD_R
const plotH = H - PAD_T - PAD_B

/** Arrondit le maximum à 1, 2, 5 ou 10 × une puissance de 10 pour des graduations lisibles. */
function niceMax(m) {
  if (m <= 0) return 1
  const pow = 10 ** Math.floor(Math.log10(m))
  const n = m / pow
  return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * pow
}

const max = computed(() => niceMax(Math.max(0, ...props.values)))

const ticks = computed(() =>
  [0, 1, 2, 3, 4].map((i) => ({ value: (max.value / 4) * i, y: PAD_T + plotH - (plotH / 4) * i })),
)

function monthDate(key) {
  const [y, m] = String(key).split('-').map(Number)
  return new Date(y, m - 1, 1)
}
const monthShort = (key) => monthDate(key).toLocaleDateString('fr-FR', { month: 'short' })
const monthLong = (key) => monthDate(key).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })

function compact(v) {
  if (v >= 1_000_000) return `${+(v / 1_000_000).toFixed(1)} M`
  if (v >= 1_000) return `${+(v / 1_000).toFixed(1)} k`
  return String(Math.round(v))
}

const bars = computed(() => {
  const n = props.values.length || 1
  const slot = plotW / n
  const w = slot * 0.6
  return props.values.map((v, i) => {
    const h = (v / max.value) * plotH
    return {
      x: PAD_L + i * slot + (slot - w) / 2,
      cx: PAD_L + i * slot + slot / 2,
      y: PAD_T + plotH - h,
      w,
      h,
      label: monthShort(props.labels[i]),
      title: `${monthLong(props.labels[i])} : ${props.formatValue(v)}`,
      last: i === n - 1, // le mois en cours est mis en avant
    }
  })
})
</script>

<template>
  <svg :viewBox="`0 0 ${W} ${H}`" class="chart" role="img" :aria-label="`Graphique en barres sur ${values.length} mois`">
    <g v-for="t in ticks" :key="t.y">
      <line :x1="PAD_L" :x2="W - PAD_R" :y1="t.y" :y2="t.y" stroke="#dde3da" stroke-width="1" />
      <text :x="PAD_L - 6" :y="t.y + 3" text-anchor="end" font-size="10" fill="#4a5a50">{{ compact(t.value) }}</text>
    </g>
    <g v-for="(b, i) in bars" :key="i">
      <rect :x="b.x" :y="b.y" :width="b.w" :height="b.h" rx="3" :fill="color" :opacity="b.last ? 1 : 0.35">
        <title>{{ b.title }}</title>
      </rect>
      <text :x="b.cx" :y="H - 10" text-anchor="middle" font-size="10" fill="#4a5a50">{{ b.label }}</text>
    </g>
  </svg>
</template>