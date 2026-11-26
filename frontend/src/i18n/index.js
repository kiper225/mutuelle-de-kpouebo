import { reactive, watchEffect } from 'vue'
import { createI18n } from 'vue-i18n'
import fr from './fr'
import en from './en'

export const LOCALES = { fr: 'Français', en: 'English' }
const STORAGE_KEY = 'kp_locale'

const savedChoice = () => {
  try {
    const value = localStorage.getItem(STORAGE_KEY)
    return LOCALES[value] ? value : null
  } catch {
    return null
  }
}

// Ordre : choix déjà fait sur cet appareil, puis langue du navigateur, puis français.
function detect() {
  const saved = savedChoice()
  if (saved) return saved
  const browser = String(navigator.language || 'fr').slice(0, 2).toLowerCase()
  return LOCALES[browser] ? browser : 'fr'
}

export const i18n = createI18n({
  legacy: false,
  locale: detect(),
  fallbackLocale: 'fr',
  messages: { fr, en },
})

document.documentElement.lang = i18n.global.locale.value

/** Traduction utilisable hors des composants (fichiers .js). */
export const t = (...args) => i18n.global.t(...args)

/** Langue des dates et des nombres : « fr-FR » ou « en-GB » (jour/mois/année dans les deux cas). */
export const intlLocale = () => (i18n.global.locale.value === 'en' ? 'en-GB' : 'fr-FR')

/** Change la langue ; remember = true enregistre le choix du visiteur sur cet appareil. */
export function setLocale(code, remember = true) {
  if (!LOCALES[code]) return
  i18n.global.locale.value = code
  document.documentElement.lang = code
  if (remember) {
    try {
      localStorage.setItem(STORAGE_KEY, code)
    } catch {
      /* stockage indisponible */
    }
  }
}

/** À la connexion : on reprend la langue du compte, sauf si le visiteur en a déjà choisi une sur cet appareil. */
export function applyUserLocale(code) {
  if (!savedChoice() && LOCALES[code]) setLocale(code, false)
}

/**
 * Libellés fixes traduisibles, sans changer le code existant : le français reste celui de `source`,
 * et l'anglais vient du catalogue (clé « groupe.clé »). Les objets restent utilisables comme avant (GENDER[key], v-for…).
 */
export function labels(group, source) {
  const out = reactive({})
  watchEffect(() => {
    for (const [key, fallback] of Object.entries(source)) {
      const path = `${group}.${key}`
      out[key] = i18n.global.te(path) ? i18n.global.t(path) : fallback
    }
  })
  return out
}