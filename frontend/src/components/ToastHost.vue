<script setup>
import { useRouter } from 'vue-router'
import { toasts, dismissToast } from '../composables/notifications'

const router = useRouter()

function open(toast) {
  dismissToast(toast.id)
  if (toast.to) router.push(toast.to)
}
</script>

<template>
  <div class="toasts" aria-live="polite">
    <div v-for="t in toasts" :key="t.id" class="toast" role="status">
      <button type="button" class="toast-main" @click="open(t)">
        <span class="toast-icon">{{ t.icon }}</span>
        <span>
          <b>{{ t.title }}</b>
          <span v-if="t.body" class="toast-body">{{ t.body }}</span>
        </span>
      </button>
      <button type="button" class="toast-x" aria-label="Fermer" @click="dismissToast(t.id)">×</button>
    </div>
  </div>
</template>