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
export const toasts = reactive([]) // bulles d'alerte affichées à l'écran

const REFRESH_EVERY = 30000
const BROWSER_KEY = 'kp_browser_notifs'
let timer = null
let known = null // notifications déjà vues (null avant le premier chargement : pas de bulles pour l'existant)
let lastMessageId = 0
let toastId = 0

/* ---------- Bulles d'alerte ---------- */
export function dismissToast(id) {
  const index = toasts.findIndex((t) => t.id === id)
  if (index >= 0) toasts.splice(index, 1)
}

function pushToast(toast) {
  const id = ++toastId
  toasts.push({ id, ...toast })
  setTimeout(() => dismissToast(id), 7000)
}

/* ---------- Notifications du navigateur (facultatives) ---------- */
export function browserEnabled() {
  try {
    return localStorage.getItem(BROWSER_KEY) === '1' && 'Notification' in window && Notification.permission === 'granted'
  } catch {
    return false
  }
}

export async function enableBrowserNotifications() {
  if (!('Notification' in window)) return false
  const result = await Notification.requestPermission()
  try {
    localStorage.setItem(BROWSER_KEY, result === 'granted' ? '1' : '0')
  } catch {
    /* stockage indisponible */
  }
  return result === 'granted'
}

/** Onglet en arrière-plan : notification du navigateur ; sinon : bulle dans la page. */
function announce({ icon, title, body, to, tag }) {
  if (document.hidden && browserEnabled()) {
    try {
      const n = new Notification(title, { body, icon: '/logo.jpg', tag })
      n.onclick = () => {
        window.focus()
        if (to) router.push(to)
        n.close()
      }
      return
    } catch {
      /* on retombe sur la bulle */
    }
  }
  pushToast({ icon, title, body, to })
}

function viewingChat(senderId) {
  const route = router.currentRoute.value
  return route.path === '/messagerie' && Number(route.query.avec) === senderId && !document.hidden
}

/* ---------- Chargement ---------- */
export async function refreshNotifications() {
  try {
    const res = await api.get('/notifications')
    const baseline = known === null
    const fresh = baseline ? [] : res.items.filter((n) => !n.read && !known.has(n.id))

    notifState.items = res.items
    notifState.unread = res.unread
    notifState.messages = res.messages
    known = new Set(res.items.map((n) => n.id))

    for (const n of fresh.slice(0, 3)) {
      announce({ icon: kindOf(n).icon, title: n.title, body: n.body, to: n.url, tag: n.id })
    }

    const m = res.messages.latest
    if (baseline) {
      lastMessageId = m?.id ?? 0
    } else if (m && m.id > lastMessageId) {
      lastMessageId = m.id
      if (!viewingChat(m.sender_id)) {
        announce({ icon: '💬', title: m.name, body: m.preview, to: `/messagerie?avec=${m.sender_id}`, tag: `msg-${m.id}` })
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
  refreshNotifications()
  timer = setInterval(() => {
    if (document.visibilityState === 'visible') refreshNotifications()
  }, REFRESH_EVERY)
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