<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { site } from '../content'
import PublicationCard from '../components/PublicationCard.vue'
import PresidentCard from '../components/PresidentCard.vue'
import VideoBlock from '../components/VideoBlock.vue'
import ValuesGrid from '../components/ValuesGrid.vue'
import BureauGrid from '../components/BureauGrid.vue'
import HeroCarousel from '../components/HeroCarousel.vue'
import NewsCarousel from '../components/NewsCarousel.vue'
import FacesCarousel from '../components/FacesCarousel.vue'

const latest = ref([])
const loadError = ref('')
// Carrousel d'accueil et visages : gérés depuis l'administration.
// Sans diapositive enregistrée, on garde celles de content.js.
const slides = ref(site.slides || [])
const faces = ref([])

const mapSlide = (s) => ({
  eyebrow: s.eyebrow || '',
  title: s.title,
  text: s.text || '',
  image: s.image_url || '',
  cta: s.cta_label && s.cta_to ? { label: s.cta_label, to: s.cta_to } : null,
  cta2: s.cta2_label && s.cta2_to ? { label: s.cta2_label, to: s.cta2_to } : null,
})

const mapFace = (f) => ({ name: f.name, role: f.role || '', text: f.text || '', photo: f.photo_url || '' })


onMounted(async () => {
  try {
    const res = await api.get('/publications')
    latest.value = res.data // jusqu'à 9 publications récentes
  } catch (e) {
    loadError.value = e.message
  }

  try {
    const home = await api.get('/home-content')
    if (home.slides.length) slides.value = home.slides.map(mapSlide)
    faces.value = home.faces.map(mapFace)
  } catch {
    /* on garde les diapositives de content.js */
  }
})
</script>

<template>
  <main>
    <HeroCarousel :slides="slides" />

    <section class="section alt">
      <div class="container">
        <PresidentCard />
      </div>
    </section>

    <section class="section center">
      <div class="container">
        <h2 class="text-3xl md:text-4xl font-bold" style="color: var(--green)">Nous sommes la {{ site.name }}</h2>
        <p class="narrow" style="margin-bottom: 32px">{{ site.about }}</p>
        <VideoBlock />
      </div>
    </section>

    <section class="section band-green">
      <div class="container">
        <h2 class="center text-3xl md:text-4xl font-bold">Découvrez nos axes d'action</h2>
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
          <h2 style="color: var(--green)" class="text-3xl md:text-4xl font-bold">{{ site.village.title }}</h2>
          <p>{{ site.village.text }}</p><br />
          <RouterLink to="/chantiers?category=realisation" class="btn green">Voir l'histoire de Kpouèbo</RouterLink>
        </div>
      </div>
    </section>

    <section class="section alt">
      <div class="container">
        <h2 style="color: var(--green)" class="text-3xl md:text-4xl font-bold">Dernières actualités</h2>
        <p v-if="loadError" class="alert err">{{ loadError }}</p>
        <p v-else-if="!latest.length" class="muted">Aucune publication pour le moment.</p>
        <NewsCarousel v-else :items="latest" />
        <p><RouterLink to="/actualites" class="link">Toutes les actualités</RouterLink></p>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <h2 class="center text-3xl md:text-4xl font-bold" style="color: var(--green)">Les valeurs qui guident notre action</h2>
        <p class="center narrow" style="margin-bottom: 32px">
          La mutuelle de développement de Kpouèbo s’appuie sur des valeurs fortes pour mener à bien sa mission.
          Car ce qui nous unit est basée sur des principes, mais aussi par une conviction profonde de solidarité et de responsabilité favorisée par des projets concrets, innovants avec une jeunesse engagée.
        </p>
        <ValuesGrid />
      </div>
    </section>

    <section class="section alt">
      <div class="container">
        <h2 class="center text-3xl md:text-4xl font-bold" style="color: var(--green)">Le bureau de la mutuelle</h2>
        <p class="center narrow" style="margin-bottom: 32px">
          La mutuelle est composée d’un bureau élu par les membres. Il est chargé de la gestion et de l’administration, ainsi que de la mise en œuvre des projets et des actions décidés par l’assemblée générale.
        </p>
        <BureauGrid />
      </div>
    </section>
    <section v-if="faces.length" class="section">
      <div class="container">
        <h2 class="center text-3xl md:text-4xl font-bold" style="color: var(--green)">{{ site.faces?.title || 'Visages emblématiques de Kpouèbo' }}</h2>
        <p v-if="site.faces?.intro" class="center narrow" style="margin-bottom: 32px">{{ site.faces.intro }}</p>
        <FacesCarousel :items="faces" />
      </div>
    </section>
  </main>
</template>
