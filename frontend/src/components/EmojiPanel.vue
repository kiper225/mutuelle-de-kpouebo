<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { EMOJI_GROUPS } from '../emojis'
import { STICKERS } from '../stickers'

const emit = defineEmits(['pick', 'sticker', 'close'])

const RECENT_KEY = 'kp_recent_emojis'
const tab = ref('emoji') // 'emoji' | 'sticker'
const group = ref('')

function loadRecents() {
  try {
    const value = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]')
    return Array.isArray(value) ? value.slice(0, 24) : []
  } catch {
    return []
  }
}
const recents = ref(loadRecents())

const groups = computed(() =>
  recents.value.length ? [{ key: 'recents', label: '🕘', title: 'Récents', items: recents.value }, ...EMOJI_GROUPS] : EMOJI_GROUPS,
)
const current = computed(() => groups.value.find((g) => g.key === group.value) || groups.value[0])

function pick(emoji) {
  emit('pick', emoji)
  recents.value = [emoji, ...recents.value.filter((e) => e !== emoji)].slice(0, 24)
  try {
    localStorage.setItem(RECENT_KEY, JSON.stringify(recents.value))
  } catch {
    /* stockage indisponible : les récents ne sont simplement pas conservés */
  }
}

const onKey = (e) => {
  if (e.key === 'Escape') emit('close')
}
onMounted(() => document.addEventListener('keydown', onKey))
onBeforeUnmount(() => document.removeEventListener('keydown', onKey))
</script>

<template>
  <div class="emoji-panel">
    <div class="emoji-tabs" role="tablist">
      <button type="button" role="tab" class="emoji-tab" :class="{ on: tab === 'emoji' }" :aria-selected="tab === 'emoji'" @click="tab = 'emoji'">Emojis</button>
      <button type="button" role="tab" class="emoji-tab" :class="{ on: tab === 'sticker' }" :aria-selected="tab === 'sticker'" @click="tab = 'sticker'">Autocollants</button>
    </div>

    <template v-if="tab === 'emoji'">
      <div class="emoji-groups">
        <button
          v-for="g in groups" :key="g.key" type="button" class="emoji-group" :class="{ on: g.key === current.key }"
          :title="g.title" :aria-label="g.title" @click="group = g.key"
        >{{ g.label }}</button>
      </div>
      <div class="emoji-grid">
        <button v-for="e in current.items" :key="e" type="button" class="emoji-btn" @click="pick(e)">{{ e }}</button>
      </div>
    </template>

    <div v-else class="sticker-grid">
      <button v-for="(s, id) in STICKERS" :key="id" type="button" class="sticker-btn" @click="emit('sticker', id)">
        <span class="em">{{ s.emoji }}</span>
        <span class="lb">{{ s.label }}</span>
      </button>
    </div>
  </div>
</template>