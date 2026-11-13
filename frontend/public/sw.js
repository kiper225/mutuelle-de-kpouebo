// Petit service worker : il affiche les alertes sur mobile et ouvre le site quand on clique dessus.
self.addEventListener('install', () => self.skipWaiting())
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()))

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const path = event.notification.data?.url || '/'

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
      const open = windows.find((w) => w.url.startsWith(self.location.origin))

      if (open) {
        // Le site est déjà ouvert : on l'affiche et on lui demande d'aller à la bonne page.
        return open.focus().then((client) => (client || open).postMessage({ type: 'navigate', url: path }))
      }

      return self.clients.openWindow(new URL(path, self.location.origin).href)
    }),
  )
})