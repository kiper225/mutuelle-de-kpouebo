<script setup>
import { reactive, ref, onMounted } from 'vue'
import { api } from '../../api'
import { CATEGORIES, categoryLabel, formatDate } from '../../utils'
import AdminNav from '../../components/AdminNav.vue'
import Pager from '../../components/Pager.vue'
import PhotoField from '../../components/PhotoField.vue'

const emptyForm = () => ({
  id: null, title: '', category: 'actualite', excerpt: '', body: '', publish: true,
  cover: null, cover_url: '', remove_cover: false,
})

const items = ref([])
const page = ref(1)
const last = ref(1)
const form = reactive(emptyForm())
const fileKey = ref(0)
const errors = ref({})
const error = ref('')
const success = ref('')
const saving = ref(false)

const err = (name) => errors.value[name]?.[0] || ''

async function load(p = 1) {
  try {
    const res = await api.get('/admin/publications', { page: p })
    items.value = res.data
    page.value = res.current_page
    last.value = res.last_page
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
    id: item.id, title: item.title, category: item.category, excerpt: item.excerpt || '',
    body: item.body, publish: !!item.published_at, cover: null,
    cover_url: item.cover_url || '', remove_cover: false,
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
    fd.append('category', form.category)
    fd.append('excerpt', form.excerpt)
    fd.append('body', form.body)
    fd.append('publish', form.publish ? '1' : '0')
    if (form.cover) fd.append('cover', form.cover)
    if (form.id) fd.append('_method', 'PUT') // envoi multipart : Laravel lit _method
    if (form.remove_cover && !form.cover) fd.append('remove_cover', '1')

    await api.post(form.id ? `/admin/publications/${form.id}` : '/admin/publications', fd)
    success.value = form.id ? 'Publication modifiée.' : 'Publication créée.'
    reset()
    await load(1)
  } catch (e) {
    errors.value = e.errors
    error.value = Object.keys(e.errors).length ? 'Veuillez corriger les champs indiqués.' : e.message
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!confirm(`Supprimer « ${item.title} » ? Les commentaires associés seront supprimés.`)) return
  try {
    await api.del(`/admin/publications/${item.id}`)
    if (form.id === item.id) reset()
    await load(page.value)
  } catch (e) {
    error.value = e.message
  }
}

const newPhoto = ref(null)
const photoKey = ref(0)
const photoSaving = ref(false)

async function savePhoto() {
  if (!newPhoto.value) return
  photoSaving.value = true
  error.value = ''
  success.value = ''
  try {
    const fd = new FormData()
    fd.append('photo', newPhoto.value)
    member.value = await api.post(`/admin/members/${id}/photo`, fd)
    newPhoto.value = null
    photoKey.value++
    success.value = 'Photo enregistrée.'
  } catch (e) {
    error.value = e.field('photo') || e.message
  } finally {
    photoSaving.value = false
  }
}

onMounted(() => load())
</script>

<template>
  <div>
    <div class="page-banner"><div class="container"><h1>Publications</h1></div></div>
    <section class="section alt">
      <div class="container">
        <AdminNav />

        <form class="card form" style="margin-bottom: 24px" @submit.prevent="save">
          <h2 style="color: var(--green)">{{ form.id ? 'Modifier la publication' : 'Nouvelle publication' }}</h2>
          <p v-if="error" class="alert err">{{ error }}</p>
          <p v-if="success" class="alert ok">{{ success }}</p>

          <div class="row">
            <div class="field" style="flex: 2 1 320px">
              <label for="title">Titre</label>
              <input id="title" v-model="form.title" type="text" required maxlength="200" />
              <span class="error">{{ err('title') }}</span>
            </div>
            <div class="field">
              <label for="category">Catégorie</label>
              <select id="category" v-model="form.category">
                <option v-for="(label, key) in CATEGORIES" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
          </div>
          <div class="fiel">
            <label for="excerpt">Résumé (facultatif, 500 caractères maximum)</label>
            <textarea id="excerpt" v-model="form.excerpt" rows="2" maxlength="500"></textarea>
            <span class="error">{{ err('excerpt') }}</span>
          </div>
          <div class="fiel">
            <label for="body">Contenu</label>
            <textarea id="body" v-model="form.body" rows="8" required></textarea>
            <span class="error">{{ err('body') }}</span>
          </div>
          <label for="cover">Image de couverture (facultatif{{ form.id ? ', remplace l’actuelle' : '' }})</label>
          <div class="p-3 bg-layer border border-layer-line rounded-xl">
            <div class="mb-1 flex justify-between items-center">
              <PhotoField
                :key="fileKey"
                v-model="form.cover"
                label="Image de couverture (facultatif)"
                :aspect="16 / 9"
                :output-width="1280"
                :current-url="form.id && !form.remove_cover ? form.cover_url : ''"
                :error="err('cover')"
                hint="Vous pourrez recadrer l'image avant l'envoi."
                class="flex items-center gap-x-3"
              />
              <label v-if="form.id && form.cover_url && !form.cover" class="check">
                <input v-model="form.remove_cover" type="checkbox" />
                Supprimer l'image actuelle
              </label>
              <span class="error">{{ err('cover') }}</span>
            </div>
          </div>
          <label class="check">
            <input v-model="form.publish" type="checkbox" />
            Publier maintenant (décochez pour garder en brouillon)
          </label>
          <div class="actions">
            <button class="btn orange" type="submit" :disabled="saving">{{ saving ? 'Enregistrement…' : form.id ? 'Enregistrer' : 'Publier' }}</button>
            <button v-if="form.id" class="btn outline-green" type="button" @click="reset">Annuler la modification</button>
          </div>
        </form>

        <div class="card">
          <h2 style="color: var(--green)">Toutes les publications</h2>
          <p v-if="!items.length" class="muted">Aucune publication.</p>
          <div v-else class="table-wrap">
            <table>
              <thead><tr><th>Titre</th><th>Catégorie</th><th>État</th><th>Date</th><th>Actions</th></tr></thead>
              <tbody>
                <tr v-for="p in items" :key="p.id">
                  <td>
                    <RouterLink v-if="p.published_at" :to="`/actualites/${p.slug}`">{{ p.title }}</RouterLink>
                    <span v-else>{{ p.title }}</span>
                  </td>
                  <td>{{ categoryLabel(p.category) }}</td>
                  <td><span class="badge" :class="{ warn: !p.published_at }">{{ p.published_at ? 'Publié' : 'Brouillon' }}</span></td>
                  <td>{{ formatDate(p.published_at || p.created_at) }}</td>
                  <td>
                    <div class="actions">
                      <button class="btn outline-green small" @click="edit(p)">Modifier</button>
                      <button class="btn danger small" @click="remove(p)">Supprimer</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pager :page="page" :last="last" @change="load" />
        </div>
      </div>
    </section>
  </div>
</template>
