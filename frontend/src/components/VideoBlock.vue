<script setup>
import { site } from '../content'

// Accepte un lien YouTube classique (watch, youtu.be, shorts, embed) ou un lien Vimeo player,
// et le convertit en adresse d'intégration. Tout autre lien est refusé.
function toEmbedUrl(raw) {
  if (typeof raw !== 'string' || !raw.trim()) return ''
  let url
  try {
    url = new URL(raw.trim())
  } catch {
    return ''
  }
  if (url.protocol !== 'https:') return ''

  const host = url.hostname.replace(/^(www\.|m\.)/, '')
  const isId = (s) => /^[\w-]{11}$/.test(s || '')
  const embed = (id) => (isId(id) ? `https://www.youtube-nocookie.com/embed/${id}` : '')

  if (host === 'youtu.be') return embed(url.pathname.slice(1).split('/')[0])
  if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
    const parts = url.pathname.split('/').filter(Boolean)
    if (url.pathname === '/watch') return embed(url.searchParams.get('v'))
    if (['embed', 'shorts', 'live'].includes(parts[0])) return embed(parts[1])
    return ''
  }
  if (host === 'player.vimeo.com') return url.href
  return ''
}

const embedUrl = toEmbedUrl(site.videoUrl)
const invalid = !!site.videoUrl && !embedUrl
</script>

<template>
  <div class="video ph">
    <iframe
      v-if="embedUrl"
      :src="embedUrl"
      title="Vidéo de présentation de la mutuelle"
      allow="accelerometer; encrypted-media; picture-in-picture"
      allowfullscreen
      loading="lazy"
    ></iframe>
    <template v-else>
      <div class="play" aria-hidden="true">▶</div>
      <span class="video-label">
        {{ invalid ? '[Lien vidéo non reconnu : utilisez un lien YouTube complet]' : '[Vidéo de présentation]' }}
      </span>
    </template>
  </div>
</template>