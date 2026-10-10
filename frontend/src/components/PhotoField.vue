<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import ImageCropper from './ImageCropper.vue'

const props = defineProps({
  modelValue: { type: File, default: null },
  label: { type: String, default: 'Photo' },
  aspect: { type: Number, default: 3 / 4 },
  outputWidth: { type: Number, default: 600 },
  currentUrl: { type: String, default: '' }, // image déjà enregistrée, affichée tant qu'aucune nouvelle n'est choisie
  required: { type: Boolean, default: false },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
})



const emit = defineEmits(['update:modelValue'])

const MAX_ORIGINAL = 15 * 1024 * 1024
const input = ref(null)
const original = ref(null) // fichier choisi, conservé pour pouvoir recadrer à nouveau
const cropping = ref(false)
const previewUrl = ref('')
const localError = ref('')
const id = `photo-${Math.random().toString(36).slice(2, 8)}`

function setPreview(file) {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = file ? URL.createObjectURL(file) : ''
}

function resetInput() {
  if (input.value) input.value.value = ''
}

function onPick(event) {
  localError.value = ''
  const file = event.target.files[0]
  if (!file) return
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    localError.value = 'Format non accepté (JPG, PNG ou WebP).'
    resetInput()
    return
  }
  if (file.size > MAX_ORIGINAL) {
    localError.value = 'Image trop lourde (15 Mo maximum).'
    resetInput()
    return
  }
  original.value = file
  cropping.value = true
}

function onDone(file) {
  setPreview(file)
  emit('update:modelValue', file)
  cropping.value = false
}

function onCancel() {
  cropping.value = false
  if (!props.modelValue) {
    original.value = null
    resetInput()
  }
}

// Si le parent remet la valeur à vide (après enregistrement), on réinitialise le champ.
watch(
  () => props.modelValue,
  (value) => {
    if (!value) {
      setPreview(null)
      original.value = null
      resetInput()
    }
  },
)

onBeforeUnmount(() => setPreview(null))
</script>

<template>
  <div class="field">
    <label :for="id">{{ label }}</label>
    <div class="photo-field">
      <img
        v-if="previewUrl || currentUrl"
        :src="previewUrl || currentUrl"
        :class="['photo-preview', { wide: aspect >= 1 }]"
        alt="Aperçu"
      />
      <div>
        <input
          :id="id"
          ref="input"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          :required="required && !currentUrl && !modelValue"
          @change="onPick"
        />
        <button v-if="original && modelValue" type="button" class="btn outline-green small" style="margin-top: 8px" @click="cropping = true">
          Recadrer à nouveau
        </button>
      </div>
    </div>
    <span v-if="hint" class="muted">{{ hint }}</span>
    <span class="error">{{ localError || error }}</span>

    <ImageCropper
      v-if="cropping && original"
      :file="original"
      :aspect="aspect"
      :output-width="outputWidth"
      :title="aspect >= 1 ? 'Recadrer l’image' : 'Recadrer la photo'"
      @done="onDone"
      @cancel="onCancel"
    />
  </div>
</template>