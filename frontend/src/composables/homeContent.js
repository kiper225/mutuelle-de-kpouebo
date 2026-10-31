import { reactive, computed } from 'vue'
import { api } from '../api'
import { site } from '../content'

const state = reactive({ loaded: false, data: null })
let inflight = null

/** Recharge le contenu de l'accueil (une seule requête à la fois). */
function refresh() {
  if (!inflight) {
    inflight = api
      .get('/home-content')
      .then((res) => {
        state.data = res
      })
      .catch((e) => {
        // serveur injoignable : on garde les valeurs de content.js, mais on le signale dans la console (F12)
        console.warn('Contenu de l’accueil indisponible :', e.message)
      })
      .finally(() => {
        state.loaded = true
        inflight = null
      })
  }
  return inflight
}

const mapSlide = (s) => ({
  eyebrow: s.eyebrow || '',
  title: s.title,
  text: s.text || '',
  image: s.image_url || '',
  cta: s.cta_label && s.cta_to ? { label: s.cta_label, to: s.cta_to } : null,
  cta2: s.cta2_label && s.cta2_to ? { label: s.cta2_label, to: s.cta2_to } : null,
})
const mapFace = (f) => ({ name: f.name, role: f.role || '', text: f.text || '', photo: f.photo_url || '' })
const mapBureau = (i) => ({ name: i.title, role: i.subtitle || '', quote: i.text || '', photo: i.image_url || '' })
const mapIllustrated = (i) => ({ title: i.title, text: i.text || '', icon: i.icon || '', image: i.image_url || '' })

/**
 * Contenu géré depuis l'administration, avec repli sur content.js tant qu'une section n'a aucun élément enregistré.
 * Avant la fin du chargement, les listes sont vides : on évite d'afficher un instant les textes d'exemple.
 */
export function useHomeContent() {
  refresh()

  const managed = (key) => !!state.data?.managed?.[key]
  const section = (key, map, fallback) => {
    if (!state.loaded) return []
    return managed(key) ? (state.data[key] || []).map(map) : fallback || []
  }

  return {
    ready: computed(() => state.loaded),
    slides: computed(() => {
      if (!state.loaded) return []
      return state.data?.slides?.length ? state.data.slides.map(mapSlide) : site.slides || []
    }),
    faces: computed(() => (state.data?.faces || []).map(mapFace)),
    bureau: computed(() => section('bureau', mapBureau, site.bureau)),
    axes: computed(() => section('axes', mapIllustrated, site.axes)),
    values: computed(() => section('valeurs', mapIllustrated, site.values)),
  }
}