import { reactive, computed } from 'vue'
import { api, tokenStore, setUnauthorizedHandler } from './api'

export const auth = reactive({ user: null, unread: 0 })
export const isLoggedIn = computed(() => !!auth.user)
export const isAdmin = computed(() => auth.user?.role === 'admin')

export async function initAuth() {
  if (!tokenStore.get()) return
  try {
    auth.user = await api.get('/me')
  } catch {
    tokenStore.clear()
    auth.user = null
  }
}

/** Met à jour le nombre de messages non lus affiché dans le menu. */
export async function refreshUnread() {
  if (!auth.user) {
    auth.unread = 0
    return
  }
  try {
    auth.unread = (await api.get('/messages/unread-count')).count
  } catch {
    /* on garde la dernière valeur connue */
  }
}

export async function login(loginValue, password) {
  const res = await api.post('/login', { login: loginValue, password })
  tokenStore.set(res.token)
  auth.user = res.user
  return res.user
}

export async function logout() {
  try {
    await api.post('/logout')
  } catch {
    /* on déconnecte quand même côté navigateur */
  }
  tokenStore.clear()
  auth.user = null
  auth.unread = 0
}

setUnauthorizedHandler(() => {
  tokenStore.clear()
  auth.user = null
  auth.unread = 0
})