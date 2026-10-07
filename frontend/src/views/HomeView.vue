<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { site } from '../content'
import PublicationCard from '../components/PublicationCard.vue'
import PresidentCard from '../components/PresidentCard.vue'
import VideoBlock from '../components/VideoBlock.vue'
import ValuesGrid from '../components/ValuesGrid.vue'
import BureauGrid from '../components/BureauGrid.vue'

const latest = ref([])
const loadError = ref('')

const heroStyle = site.heroImage
  ? { backgroundImage: `linear-gradient(rgba(15,90,46,.88), rgba(15,90,46,.88)), url(${site.heroImage})` }
  : {}

onMounted(async () => {
  try {
    const res = await api.get('/publications')
    latest.value = res.data.slice(0, 3)
  } catch (e) {
    loadError.value = e.message
  }
})
</script>

<template>
  <main>
    <section class="hero" :class="{ 'has-image': site.heroImage }" :style="heroStyle">
      <div class="container">
        <div class="eyebrow">UNE SEULE MISSION</div>
        <h1>{{ site.slogan }}</h1>
        <p>{{ site.hero }}</p>
        <div class="row">
          <RouterLink to="/inscription" class="btn orange">Devenir membre</RouterLink>
          <RouterLink to="/don" class="btn outline">Faire un don</RouterLink>
        </div>
      </div>
    </section>

    <section class="section alt">
      <div class="container"><PresidentCard /></div>
    </section>

    <section class="section center">
      <div class="container">
        <h2 style="color: var(--green)">Nous sommes la {{ site.name }}</h2>
        <p class="narrow" style="margin-bottom: 32px">{{ site.about }}</p>
        <VideoBlock />
      </div>
    </section>

    <section class="section band-green">
      <div class="container">
        <h2 class="center">Découvrez nos axes d'action</h2>
        <div class="grid">
          <div v-for="axe in site.axes" :key="axe.title" class="card">
            <b>{{ axe.title }}</b><br />{{ axe.text }}
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container split">
        <img v-if="site.village.image" :src="site.village.image" :alt="site.village.title" class="split-img" />
        <div v-else class="split-img ph">[CARTE OU PHOTO AÉRIENNE DE KPOUÈBO]</div>
        <div>
          <h2 style="color: var(--green)">{{ site.village.title }}</h2>
          <p>{{ site.village.text }}</p>
          <RouterLink to="/chantiers?category=realisation" class="btn green">Voir nos réalisations</RouterLink>
        </div>
      </div>
    </section>

    <section class="section alt">
      <div class="container">
        <h2 style="color: var(--green)">Dernières actualités</h2>
        <p v-if="loadError" class="alert err">{{ loadError }}</p>
        <p v-else-if="!latest.length" class="muted">Aucune publication pour le moment.</p>
        <div v-else class="grid">
          <PublicationCard v-for="p in latest" :key="p.id" :pub="p" />
        </div>
        <p><RouterLink to="/actualites" class="link">Toutes les actualités</RouterLink></p>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <h2 class="center" style="color: var(--green)">Les valeurs qui guident notre action</h2>
        <ValuesGrid />
      </div>
    </section>

    <section class="section alt">
      <div class="container">
        <h2 class="center" style="color: var(--green)">Le bureau de la mutuelle</h2>
        <BureauGrid />
      </div>
    </section>
  </main>
</template>
