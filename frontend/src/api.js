const BASE = (import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1').replace(/\/$/, '')
const TOKEN_KEY = 'mutuelle_token'

export const tokenStore = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (t) => localStorage.setItem(TOKEN_KEY, t),
  clear: () => localStorage.removeItem(TOKEN_KEY),
}

export class ApiError extends Error {
  constructor(status, body) {
    super(body?.message || 'Une erreur est survenue.')
    this.status = status
    this.errors = body?.errors || {}
  }
  /** Premier message d'erreur d'un champ. */
  field(name) {
    return this.errors[name]?.[0] || ''
  }
}

let onUnauthorized = null
export function setUnauthorizedHandler(fn) {
  onUnauthorized = fn
}

async function request(method, path, { body, query } = {}) {
  let url = BASE + path
  if (query) {
    const qs = new URLSearchParams(Object.entries(query).filter(([, v]) => v !== '' && v != null)).toString()
    if (qs) url += '?' + qs
  }

  const headers = { Accept: 'application/json' }
  const token = tokenStore.get()
  if (token) headers.Authorization = `Bearer ${token}`

  const options = { method, headers }
  if (body instanceof FormData) {
    options.body = body // le navigateur fixe lui-même le Content-Type
  } else if (body !== undefined) {
    headers['Content-Type'] = 'application/json'
    options.body = JSON.stringify(body)
  }

  let res
  try {
    res = await fetch(url, options)
  } catch {
    throw new ApiError(0, { message: 'Impossible de joindre le serveur. Vérifiez votre connexion.' })
  }

  if (res.status === 204) return null

  let data = null
  try {
    data = await res.json()
  } catch {
    /* corps vide ou non JSON */
  }

  if (!res.ok) {
    if (res.status === 401 && token && onUnauthorized) onUnauthorized()
    throw new ApiError(res.status, data)
  }
  return data
}

export const api = {
  get: (path, query) => request('GET', path, { query }),
  post: (path, body) => request('POST', path, { body }),
  put: (path, body) => request('PUT', path, { body }),
  del: (path) => request('DELETE', path),
}
