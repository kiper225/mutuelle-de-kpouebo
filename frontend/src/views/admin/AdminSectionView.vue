<script setup>
import { reactive, ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../../api'
import { SECTION_CONFIG } from '../../sections'
import PhotoField from '../../components/PhotoField.vue'

const route = useRoute()
const section = computed(() => String(route.meta.section))
const cfg = computed(() => SECTION_CONFIG[section.value])

const emptyForm = () => ({ id: null, title: '', subtitle: '', text: '', is_active: true, image: null, image_url: '', remove_image: false })

const items = ref([])
const form = reactive(emptyForm())
const fileKey = ref(0)
const errors = ref({})
const error = ref('')
const success = ref('')
const saving = ref(false)

const err = (name) => errors.value[name]?.[0] || ''
const initial = (name) => String(name || '?').trim().charAt(0).toUpperCase()

async function load() {
  try {
    items.value = await api.get(`/admin/sections/${section.value}`)
  } catch (e) {
    error.value = e.message
  }
}

function reset() {
  Object.assign(form, emptyForm())
  fileKey.value++
  errors.value = {}
}

function edit(item) {
  Object.assign(form, {
    id: item.id,
    title: item.title,
    subtitle: item.subtitle || '',
    text: item.text || '',
    is_active: !!item.is_active,
    image: null,
    image_url: item.image_url || '',
    remove_image: false,
  })
  fileKey.value++
  success.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function save() {
  saving.value = true
  errors.value = {}
  error.value = ''
  success.value = ''
  try {
    const fd = new FormData()
    fd.append('title', form.title)
    if (cfg.value.subtitleLabel) fd.append('subtitle', form.subtitle) // une valeur vide efface le champ
    fd.append('text', form.text)
    fd.append('is_active', form.is_active ? '1' : '0')
    if (cfg.value.image) {
      if (form.image) fd.append('image', form.image)
      if (form.remove_image && !form.image) fd.append('remove_image', '1')
    }
    if (form.id) fd.append('_method', 'PUT') // envoi multipart : Laravel lit _method

    const base = `/admin/sections/${section.value}`
    await api.post(form.id ? `${base}/${form.id}` : base, fd)
    success.value = form.id ? 'Élément modifié.' : 'Élément ajouté.'
    reset()
    await load()
  } catch (e) {
    errors.value = e.errors
    error.value = Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!confirm(`Supprimer « ${item.title} » ?`)) return
  try {
    await api.del(`/admin/sections/${section.value}/${item.id}`)
    if (form.id === item.id) reset()
    await load()
  } catch (e) {
    error.value = e.message
  }
}

async function move(index, direction) {
  const target = index + direction
  if (target < 0 || target >= items.value.length) return
  const list = [...items.value]
  ;[list[index], list[target]] = [list[target], list[index]]
  items.value = list
  try {
    await api.post(`/admin/sections/${section.value}/reorder`, { ids: list.map((i) => i.id) })
  } catch (e) {
    error.value = e.message
    await load()
  }
}

// Les trois sections partagent cette page : on recharge quand on passe de l'une à l'autre.
watch(
  section,
  () => {
    reset()
    error.value = ''
    success.value = ''
    items.value = []
    load()
  },
  { immediate: true },
)
</script>

<template>
  <section class="section">
    <div class="container">
      <p v-if="error" class="alert err">{{ error }}</p>
      <p v-if="success" class="alert ok">{{ success }}</p>

      <form class="card form" style="margin-bottom: 24px" @submit.prevent="save">
        <h2 style="color: var(--green)">{{ form.id ? `Modifier : ${cfg.singular}` : `Nouveau : ${cfg.singular}` }}</h2>

        <div class="row">
          <div class="field">
            <label for="title">{{ cfg.titleLabel }}</label>
            <input id="title" v-model="form.title" type="text" required maxlength="150" />
            <span class="error">{{ err('title') }}</span>
          </div>
          <div v-if="cfg.subtitleLabel" class="field">
            <label for="subtitle">{{ cfg.subtitleLabel }}</label>
            <input id="subtitle" v-model="form.subtitle" type="text" maxlength="150" />
            <span class="error">{{ err('subtitle') }}</span>
          </div>
        </div>
        <div class="field">
          <label for="text">{{ cfg.textLabel }} (600 caractères maximum)</label>
          <textarea id="text" v-model="form.text" rows="3" maxlength="600"></textarea>
          <span class="error">{{ err('text') }}</span>
        </div>

        <template v-if="cfg.image">
          <PhotoField
            :key="fileKey"
            v-model="form.image"
            :label="cfg.image.label"
            :aspect="cfg.image.aspect"
            :output-width="cfg.image.width"
            :current-url="form.id && !form.remove_image ? form.image_url : ''"
            :error="err('image')"
            :hint="cfg.image.hint"
          />
          <label v-if="form.id && form.image_url && !form.image" class="check">
            <input v-model="form.remove_image" type="checkbox" />
            Supprimer la photo actuelle
          </label>
        </template>

        <label class="check">
          <input v-model="form.is_active" type="checkbox" />
          Afficher sur le site
        </label>

        <div class="actions">
          <button class="btn orange" type="submit" :disabled="saving">
            {{ saving ? 'Enregistrement…' : form.id ? 'Enregistrer' : 'Ajouter' }}
          </button>
          <button v-if="form.id" class="btn outline-green" type="button" @click="reset">Annuler la modification</button>
        </div>
      </form>

      <div class="card">
        <h2 style="color: var(--green)">{{ cfg.plural }} ({{ items.length }})</h2>
        <p v-if="!items.length" class="muted">
          Aucun élément enregistré : le site affiche pour l'instant les textes d'exemple du fichier <code>content.js</code>.
          Dès que vous en ajoutez un, la liste ci-dessus les remplace.
        </p>
        <template v-else>
          <ul class="manage-list">
            <li v-for="(it, i) in items" :key="it.id" class="manage-item">
              <template v-if="cfg.image">
                <img v-if="it.image_url" :src="it.image_url" alt="" class="manage-thumb portrait" />
                <span v-else class="manage-thumb portrait manage-initial">{{ initial(it.title) }}</span>
              </template>
              <span v-else class="manage-thumb portrait manage-initial">{{ i + 1 }}</span>
              <div class="manage-info">
                <b>{{ it.title }}</b>
                <span class="muted">{{ it.subtitle || it.text }}</span>
                <span v-if="!it.is_active" class="badge warn">Masqué</span>
              </div>
              <div class="actions">
                <button class="btn outline-green small" type="button" aria-label="Monter" :disabled="i === 0" @click="move(i, -1)">↑</button>
                <button class="btn outline-green small" type="button" aria-label="Descendre" :disabled="i === items.length - 1" @click="move(i, 1)">↓</button>
                <button class="btn outline-green small" type="button" @click="edit(it)">Modifier</button>
                <button class="btn danger small" type="button" @click="remove(it)">Supprimer</button>
              </div>
            </li>
          </ul>
          <p class="muted" style="margin-bottom: 0">
            Pour masquer toute cette section sur le site, décochez « Afficher sur le site » sur chaque élément au lieu de les
            supprimer : si vous supprimez tout, les textes d'exemple de <code>content.js</code> réapparaissent.
          </p>
        </template>
      </div>
    </div>
  </section>
</template>