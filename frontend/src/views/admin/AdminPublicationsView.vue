<script setup>
import { reactive, ref, onMounted } from 'vue'
import { api } from '../../api'
import { CATEGORIES, categoryLabel, formatDate } from '../../utils'
import AdminNav from '../../components/AdminNav.vue'
import Pager from '../../components/Pager.vue'

const emptyForm = () => ({ id: null, title: '', category: 'actualite', excerpt: '', body: '', publish: true, cover: null })

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

onMounted(() => load())
</script>

<template>
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
        <div class="field">
          <label for="excerpt">Résumé (facultatif, 500 caractères maximum)</label>
          <textarea id="excerpt" v-model="form.excerpt" rows="2" maxlength="500"></textarea>
          <span class="error">{{ err('excerpt') }}</span>
        </div>
        <div class="field">
          <label for="body">Contenu</label>
          <textarea id="body" v-model="form.body" rows="8" required></textarea>
          <span class="error">{{ err('body') }}</span>
        </div>
        <div class="field">
          <label for="cover">Image de couverture (facultatif{{ form.id ? ', remplace l’actuelle' : '' }})</label>
          <input id="cover" :key="fileKey" type="file" accept="image/*" @change="form.cover = $event.target.files[0]" />
          <span class="error">{{ err('cover') }}</span>
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
</template>
