<script setup>
import { reactive, ref, onMounted } from 'vue'
import { api } from '../../api'
import PhotoField from '../../components/PhotoField.vue'

const emptyForm = () => ({
  id: null, eyebrow: '', title: '', text: '',
  cta_label: '', cta_to: '', cta2_label: '', cta2_to: '',
  is_active: true, image: null, image_url: '', remove_image: false,
})

const destinations = [
  ['/inscription', 'Devenir membre'],
  ['/don', 'Faire un don'],
  ['/chantiers?category=projet_en_cours', 'Projets en cours'],
  ['/chantiers?category=realisation', 'Réalisations'],
  ['/actualites', 'Actualités'],
  ['/la-mutuelle', 'La mutuelle'],
]

const items = ref([])
const form = reactive(emptyForm())
const fileKey = ref(0)
const errors = ref({})
const error = ref('')
const success = ref('')
const saving = ref(false)

const err = (name) => errors.value[name]?.[0] || ''

async function load() {
  try {
    items.value = await api.get('/admin/hero-slides')
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
    eyebrow: item.eyebrow || '',
    title: item.title,
    text: item.text || '',
    cta_label: item.cta_label || '',
    cta_to: item.cta_to || '',
    cta2_label: item.cta2_label || '',
    cta2_to: item.cta2_to || '',
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
    for (const key of ['eyebrow', 'title', 'text', 'cta_label', 'cta_to', 'cta2_label', 'cta2_to']) {
      fd.append(key, form[key]) // une valeur vide efface le champ
    }
    fd.append('is_active', form.is_active ? '1' : '0')
    if (form.image) fd.append('image', form.image)
    if (form.remove_image && !form.image) fd.append('remove_image', '1')
    if (form.id) fd.append('_method', 'PUT') // envoi multipart : Laravel lit _method

    await api.post(form.id ? `/admin/hero-slides/${form.id}` : '/admin/hero-slides', fd)
    success.value = form.id ? 'Diapositive modifiée.' : 'Diapositive créée.'
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
  if (!confirm(`Supprimer la diapositive « ${item.title} » ?`)) return
  try {
    await api.del(`/admin/hero-slides/${item.id}`)
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
    await api.post('/admin/hero-slides/reorder', { ids: list.map((s) => s.id) })
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
        <h2 style="color: var(--green)">{{ form.id ? 'Modifier la diapositive' : 'Nouvelle diapositive' }}</h2>

        <div class="row">
          <div class="field" style="flex: 2 1 320px">
            <label for="title">Titre</label>
            <input id="title" v-model="form.title" type="text" required maxlength="150" />
            <span class="error">{{ err('title') }}</span>
          </div>
          <div class="field">
            <label for="eyebrow">Petit texte au-dessus du titre</label>
            <input id="eyebrow" v-model="form.eyebrow" type="text" maxlength="60" placeholder="UNE SEULE MISSION" />
          </div>
        </div>
        <div class="field">
          <label for="text">Texte (400 caractères maximum)</label>
          <textarea id="text" v-model="form.text" rows="3" maxlength="400"></textarea>
          <span class="error">{{ err('text') }}</span>
        </div>

        <PhotoField
          :key="fileKey"
          v-model="form.image"
          label="Image de fond (facultatif)"
          :aspect="16 / 7"
          :output-width="1600"
          :current-url="form.id && !form.remove_image ? form.image_url : ''"
          :error="err('image')"
          hint="Image large de préférence. Vous pourrez la recadrer avant l'envoi. Sans image, la diapositive a un fond vert."
        />
        <label v-if="form.id && form.image_url && !form.image" class="check">
          <input v-model="form.remove_image" type="checkbox" />
          Supprimer l'image actuelle
        </label>

        <h3 style="margin: 8px 0 0">Boutons (facultatifs)</h3>
        <datalist id="destinations">
          <option v-for="[to, label] in destinations" :key="to" :value="to">{{ label }}</option>
        </datalist>
        <div class="row">
          <div class="field">
            <label for="cta_label">Bouton 1 : texte</label>
            <input id="cta_label" v-model="form.cta_label" type="text" maxlength="40" placeholder="Devenir membre" />
          </div>
          <div class="field">
            <label for="cta_to">Bouton 1 : page</label>
            <input id="cta_to" v-model="form.cta_to" type="text" list="destinations" placeholder="/inscription" />
            <span class="error">{{ err('cta_to') }}</span>
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label for="cta2_label">Bouton 2 : texte</label>
            <input id="cta2_label" v-model="form.cta2_label" type="text" maxlength="40" placeholder="Faire un don" />
          </div>
          <div class="field">
            <label for="cta2_to">Bouton 2 : page</label>
            <input id="cta2_to" v-model="form.cta2_to" type="text" list="destinations" placeholder="/don" />
            <span class="error">{{ err('cta2_to') }}</span>
          </div>
        </div>
        <p class="muted" style="margin: 0">Le lien doit commencer par « / » (page du site). Un bouton sans texte ou sans page n'est pas affiché.</p>

        <label class="check">
          <input v-model="form.is_active" type="checkbox" />
          Afficher sur le site
        </label>

        <div class="actions">
          <button class="btn orange" type="submit" :disabled="saving">{{ saving ? 'Enregistrement…' : form.id ? 'Enregistrer' : 'Ajouter la diapositive' }}</button>
          <button v-if="form.id" class="btn outline-green" type="button" @click="reset">Annuler la modification</button>
        </div>
      </form>

      <div class="card">
        <h2 style="color: var(--green)">Diapositives ({{ items.length }})</h2>
        <p v-if="!items.length" class="muted">
          Aucune diapositive enregistrée : le site affiche pour l'instant celle du fichier <code>content.js</code>.
        </p>
        <ul v-else class="manage-list">
          <li v-for="(s, i) in items" :key="s.id" class="manage-item">
            <img v-if="s.image_url" :src="s.image_url" alt="" class="manage-thumb" />
            <span v-else class="manage-thumb"></span>
            <div class="manage-info">
              <b>{{ s.title }}</b>
              <span class="muted">{{ s.eyebrow }}</span>
              <span v-if="!s.is_active" class="badge warn">Masquée</span>
            </div>
            <div class="actions">
              <button class="btn outline-green small" type="button" aria-label="Monter" :disabled="i === 0" @click="move(i, -1)">↑</button>
              <button class="btn outline-green small" type="button" aria-label="Descendre" :disabled="i === items.length - 1" @click="move(i, 1)">↓</button>
              <button class="btn outline-green small" type="button" @click="edit(s)">Modifier</button>
              <button class="btn danger small" type="button" @click="remove(s)">Supprimer</button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>