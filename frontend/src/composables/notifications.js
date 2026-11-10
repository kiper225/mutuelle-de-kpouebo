import { reactive } from 'vue'
import { api } from '../api'
import router from '../router'

export const KINDS = {
  publication: { icon: '📰', label: 'Information' },
  communique: { icon: '📢', label: 'Communiqué' },
  activity: { icon: '🏗️', label: 'Activité' },
  collecte: { icon: '🎁', label: 'Levée de fonds' },
  cotisation_reminder: { icon: '⏰', label: 'Rappel de cotisation' },
}
export const kindOf = (n) => KINDS[n.kind] || { icon: '🔔', label: 'Notification' }

export const notifState = reactive({ items: [], unread: 0, messages: { unread: 0, latest: null } })
export const toasts = reactive([]) // bulles dans la page : repli quand les alertes du navigateur ne sont pas disponibles

const REFRESH_EVERY = 30000
const ALERTS_KEY = 'kp_browser_notifs' // « 0 » : alertes désactivées par le membre
let timer = null
let known = null // notifications déjà vues (null avant le premier chargement : pas d'alerte pour l'existant)
let lastMessageId = 0
let toastId = 0
let workerListening = false

/* ---------- Bulles dans la page (repli) ---------- */
export function dismissToast(id) {
  const index = toasts.findIndex((t) => t.id === id)
  if (index >= 0) toasts.splice(index, 1)
}

export function pushToast(toast) {
  const id = ++toastId
  toasts.push({ id, ...toast })
  setTimeout(() => dismissToast(id), 7000)
}

/* ---------- Alertes du navigateur ---------- */
const supported = typeof window !== 'undefined' && 'Notification' in window

const readFlag = () => {
  try {
    return localStorage.getItem(ALERTS_KEY)
  } catch {
    return null
  }
}
const writeFlag = (value) => {
  try {
    localStorage.setItem(ALERTS_KEY, value)
  } catch {
    /* stockage indisponible */
  }
}

export const alertState = reactive({
  supported,
  permission: supported ? Notification.permission : 'unsupported', // default | granted | denied
  enabled: readFlag() !== '0',
})

function syncAlertState() {
  if (!supported) return
  alertState.permission = Notification.permission
  alertState.enabled = readFlag() !== '0'
}

const alertsOn = () => supported && Notification.permission === 'granted' && readFlag() !== '0'

/** Sur mobile, seules les alertes passant par le service worker fonctionnent. */
async function workerRegistration() {
  if (!('serviceWorker' in navigator)) return null
  try {
    return await Promise.race([navigator.serviceWorker.ready, new Promise((resolve) => setTimeout(() => resolve(null), 1500))])
  } catch {
    return null
  }
}

async function showAlert({ title, body, to, tag }) {
  const options = { body, icon: '/logo.jpg', badge: '/logo.jpg', tag, data: { url: to || '/' } }
  try {
    const registration = await workerRegistration()
    if (registration) {
      await registration.showNotification(title, options)
      return true
    }
    const n = new Notification(title, options)
    n.onclick = () => {
      window.focus()
      if (to) router.push(to)
      n.close()
    }
    return true
  } catch {
    return false
  }
}

/** Demande l'autorisation au navigateur (si besoin) et active les alertes. Retourne « granted », « denied » ou « default ». */
export async function enableAlerts() {
  if (!supported) return 'unsupported'
  let permission = Notification.permission
  if (permission === 'default') permission = await Notification.requestPermission()
  if (permission === 'granted') {
    writeFlag('1')
    syncAlertState()
    await showAlert({ title: 'Alertes activées', body: 'Vous serez prévenu des nouvelles informations, messages et rappels.', to: '/', tag: 'alerts-on' })
  }
  syncAlertState()
  return permission
}

export function disableAlerts() {
  writeFlag('0')
  syncAlertState()
}

/** Alerte du navigateur si elles sont activées ; sinon bulle dans la page. */
async function announce({ icon, title, body, to, tag }) {
  if (alertsOn() && (await showAlert({ title, body, to, tag }))) return
  pushToast({ icon, title, body, to })
}

function registerWorker() {
  if (!('serviceWorker' in navigator)) return
  navigator.serviceWorker.register('/sw.js').catch(() => {
    /* sans service worker, on utilise les alertes classiques */
  })
  if (!workerListening) {
    workerListening = true
    // Un clic sur une alerte demande au site déjà ouvert d'aller à la bonne page.
    navigator.serviceWorker.addEventListener('message', (event) => {
      if (event.data?.type === 'navigate' && event.data.url) router.push(event.data.url)
    })
  }
}

function viewingChat(senderId) {
  const route = router.currentRoute.value
  return route.path === '/messagerie' && Number(route.query.avec) === senderId && !document.hidden
}

/* ---------- Chargement ---------- */
export async function refreshNotifications() {
  try {
    syncAlertState()
    const res = await api.get('/notifications')
    const baseline = known === null
    const fresh = baseline ? [] : res.items.filter((n) => !n.read && !known.has(n.id))

    notifState.items = res.items
    notifState.unread = res.unread
    notifState.messages = res.messages
    known = new Set(res.items.map((n) => n.id))

    if (fresh.length > 3) {
      await announce({ icon: '🔔', title: `${fresh.length} nouvelles notifications`, body: 'Cliquez pour les consulter.', to: '/notifications', tag: 'many' })
    } else {
      for (const n of fresh) {
        await announce({ icon: kindOf(n).icon, title: n.title, body: n.body, to: n.url, tag: n.id })
      }
    }

    const m = res.messages.latest
    if (baseline) {
      lastMessageId = m?.id ?? 0
    } else if (m && m.id > lastMessageId) {
      lastMessageId = m.id
      if (!viewingChat(m.sender_id)) {
        await announce({ icon: '💬', title: m.name, body: m.preview, to: `/messagerie?avec=${m.sender_id}`, tag: `msg-${m.id}` })
      }
    }
  } catch {
    /* hors ligne ou erreur passagère : on réessaiera */
  }
}

const onVisible = () => {
  if (document.visibilityState === 'visible') refreshNotifications()
}

export function startNotifications() {
  stopNotifications()
  syncAlertState()
  registerWorker()
  refreshNotifications()
  // Le rafraîchissement continue quand l'onglet est masqué : c'est là que l'alerte du navigateur est utile.
  timer = setInterval(refreshNotifications, REFRESH_EVERY)
  document.addEventListener('visibilitychange', onVisible)
}

export function stopNotifications() {
  clearInterval(timer)
  timer = null
  document.removeEventListener('visibilitychange', onVisible)
  known = null
  lastMessageId = 0
  notifState.items = []
  notifState.unread = 0
  notifState.messages = { unread: 0, latest: null }
  toasts.splice(0, toasts.length)
}

/* ---------- Actions ---------- */
export async function markRead(n) {
  if (n.read) return
  n.read = true
  notifState.unread = Math.max(0, notifState.unread - 1)
  try {
    await api.post(`/notifications/${n.id}/read`)
  } catch {
    /* resynchronisé au prochain rafraîchissement */
  }
}

export async function markAllRead() {
  notifState.items.forEach((n) => (n.read = true))
  notifState.unread = 0
  try {
    await api.post('/notifications/read-all')
  } catch {
    /* resynchronisé au prochain rafraîchissement */
  }
}

/** « À l'instant », « Il y a 5 min », « Il y a 3 h », « Il y a 2 j », puis la date. */
export function timeAgo(iso) {
  const seconds = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000))
  if (seconds < 60) return "À l'instant"
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `Il y a ${minutes} min`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `Il y a ${hours} h`
  const days = Math.floor(hours / 24)
  if (days < 7) return `Il y a ${days} j`
  return new Date(iso).toLocaleDateString('fr-FR')
}