<script setup>
import { reactive, ref, onMounted } from 'vue'
import { api } from '../../api'
import PhotoField from '../../components/PhotoField.vue'

const emptyForm = () => ({
  id: null, name: '', role: '', text: '', is_active: true, photo: null, photo_url: '', remove_photo: false,
})

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
    items.value = await api.get('/admin/faces')
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
    name: item.name,
    role: item.role || '',
    text: item.text || '',
    is_active: !!item.is_active,
    photo: null,
    photo_url: item.photo_url || '',
    remove_photo: false,
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
    for (const key of ['name', 'role', 'text']) fd.append(key, form[key]) // une valeur vide efface le champ
    fd.append('is_active', form.is_active ? '1' : '0')
    if (form.photo) fd.append('photo', form.photo)
    if (form.remove_photo && !form.photo) fd.append('remove_photo', '1')
    if (form.id) fd.append('_method', 'PUT') // envoi multipart : Laravel lit _method

    await api.post(form.id ? `/admin/faces/${form.id}` : '/admin/faces', fd)
    success.value = form.id ? 'Visage modifié.' : 'Visage ajouté.'
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
  if (!confirm(`Supprimer « ${item.name} » de la liste ?`)) return
  try {
    await api.del(`/admin/faces/${item.id}`)
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
    await api.post('/admin/faces/reorder', { ids: list.map((f) => f.id) })
  } catch (e) {
    error.value = e.message
    await load()
  }
}

onMounted(load)
</script>

<template>
  <section class="section">
    <div class="container">
      <p v-if="error" class="alert err">{{ error }}</p>
      <p v-if="success" class="alert ok">{{ success }}</p>

      <form class="card form" style="margin-bottom: 24px" @submit.prevent="save">
        <h2 style="color: var(--green)">{{ form.id ? 'Modifier le visage' : 'Nouveau visage' }}</h2>

        <div class="row">
          <div class="field">
            <label for="name">Nom et prénom</label>
            <input id="name" v-model="form.name" type="text" required maxlength="150" />
            <span class="error">{{ err('name') }}</span>
          </div>
          <div class="field">
            <label for="role">Fonction, métier ou titre</label>
            <input id="role" v-model="form.role" type="text" maxlength="150" />
          </div>
        </div>
        <div class="field">
          <label for="text">Présentation (600 caractères maximum)</label>
          <textarea id="text" v-model="form.text" rows="4" maxlength="600"></textarea>
          <span class="error">{{ err('text') }}</span>
        </div>

        <PhotoField
          :key="fileKey"
          v-model="form.photo"
          label="Portrait"
          :aspect="4 / 5"
          :output-width="800"
          :current-url="form.id && !form.remove_photo ? form.photo_url : ''"
          :error="err('photo')"
          hint="Portrait vertical : vous pourrez le recadrer avant l'envoi."
        />
        <label v-if="form.id && form.photo_url && !form.photo" class="check">
          <input v-model="form.remove_photo" type="checkbox" />
          Supprimer la photo actuelle
        </label>

        <label class="check">
          <input v-model="form.is_active" type="checkbox" />
          Afficher sur le site
        </label>

        <div class="actions">
          <button class="btn orange" type="submit" :disabled="saving">{{ saving ? 'Enregistrement…' : form.id ? 'Enregistrer' : 'Ajouter le visage' }}</button>
          <button v-if="form.id" class="btn outline-green" type="button" @click="reset">Annuler la modification</button>
        </div>
      </form>

      <div class="card">
        <h2 style="color: var(--green)">Visages emblématiques ({{ items.length }})</h2>
        <p v-if="!items.length" class="muted">Aucun visage enregistré : la section n'apparaît pas sur le site.</p>
        <ul v-else class="manage-list">
          <li v-for="(f, i) in items" :key="f.id" class="manage-item">
            <img v-if="f.photo_url" :src="f.photo_url" alt="" class="manage-thumb portrait" />
            <span v-else class="manage-thumb portrait manage-initial">{{ initial(f.name) }}</span>
            <div class="manage-info">
              <b>{{ f.name }}</b>
              <span class="muted">{{ f.role }}</span>
              <span v-if="!f.is_active" class="badge warn">Masqué</span>
            </div>
            <div class="actions">
              <button class="btn outline-green small" type="button" aria-label="Monter" :disabled="i === 0" @click="move(i, -1)">↑</button>
              <button class="btn outline-green small" type="button" aria-label="Descendre" :disabled="i === items.length - 1" @click="move(i, 1)">↓</button>
              <button class="btn outline-green small" type="button" @click="edit(f)">Modifier</button>
              <button class="btn danger small" type="button" @click="remove(f)">Supprimer</button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>