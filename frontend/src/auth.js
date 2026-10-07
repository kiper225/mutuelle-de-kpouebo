import { reactive, computed } from 'vue'
import { api, tokenStore, setUnauthorizedHandler } from './api'

export const auth = reactive({ user: null })
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
}

setUnauthorizedHandler(() => {
  tokenStore.clear()
  auth.user = null
})
