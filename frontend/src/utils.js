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
