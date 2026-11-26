<script setup>
import { computed } from 'vue'
import { i18n, LOCALES, setLocale } from '../i18n'
import { isLoggedIn } from '../auth'
import { api } from '../api'

const current = computed(() => i18n.global.locale.value)

async function choose(code) {
  if (code === current.value) return
  setLocale(code)
  if (isLoggedIn.value) {
    try {
      await api.post('/me/locale', { locale: code }) // les notifications arriveront dans cette langue
    } catch {
      /* le choix reste appliqué sur cet appareil */
    }
  }
}
</script>

<template>
  <div class="lang-switch" role="group" :aria-label="$t('lang.label')">
    <button
      v-for="(label, code) in LOCALES" :key="code" type="button" class="lang-btn" :class="{ on: current === code }"
      :aria-pressed="current === code" :title="label" @click="choose(code)"
    >{{ code.toUpperCase() }}</button>
  </div>
</template>