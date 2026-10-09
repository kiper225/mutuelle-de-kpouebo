export const CATEGORIES = {
  projet_en_cours: 'Projet en cours',
  realisation: 'Réalisation',
  communique: 'Communiqué',
  actualite: 'Actualité',
}
export const categoryLabel = (c) => CATEGORIES[c] || c

export const PROVIDERS = {
  orange_money: 'Orange Money',
  mtn_momo: 'MTN MoMo',
  moov_money: 'Moov Money',
  wave: 'Wave',
  card: 'Carte bancaire',
  cash: 'Espèces',
}
export const providerLabel = (p) => PROVIDERS[p] || p || '—'

export const COTISATION_STATUS = { pending: 'En attente', paid: 'Payée', failed: 'Échec' }
export const MEMBER_STATUS = { pending: 'En attente', active: 'Actif', suspended: 'Suspendu' }

export function formatDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

export function formatMoney(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA'
}

export function currentPeriod() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

export const MARITAL_STATUS = {
  celibataire: 'Célibataire',
  marie: 'Marié(e)',
  divorce: 'Divorcé(e)',
  veuf: 'Veuf / Veuve',
}

export const MONTHS = [
  'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
]

/** « 2026-10-09T… » ou « 2026-10-09 » devient « 09/10/2026 » (sans décalage de fuseau). */
export function formatDateShort(value) {
  if (!value) return ''
  const [y, m, d] = String(value).slice(0, 10).split('-')
  return `${d}/${m}/${y}`
}