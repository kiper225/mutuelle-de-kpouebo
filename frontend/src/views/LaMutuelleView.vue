<script setup>
import { site } from '../content'
import PresidentCard from '../components/PresidentCard.vue'
import VideoBlock from '../components/VideoBlock.vue'
import ValuesGrid from '../components/ValuesGrid.vue'
import BureauGrid from '../components/BureauGrid.vue'
</script>

<template>
  <div class="page-banner"><div class="container"><h1>La mutuelle</h1></div></div>

  <section id="presentation" class="section center">
    <div class="container">
      <h2 style="color: var(--green)">Nous sommes la {{ site.name }}</h2>
      <p class="narrow" style="margin-bottom: 32px">{{ site.about }}</p>
      <VideoBlock />
    </div>
  </section>

  <section id="histoire" class="section alt">
    <div class="container narrow">
      <h2 style="color: var(--green)">Notre histoire</h2>
      <p class="prose">{{ site.history }}</p>
    </div>
  </section>

  <section class="section">
    <div class="container"><PresidentCard /></div>
  </section>

  <section class="section alt">
    <div class="container">
      <h2 class="center" style="color: var(--green)">Les valeurs qui guident notre action</h2>
      <ValuesGrid />
    </div>
  </section>

  <section id="bureau" class="section">
    <div class="container">
      <h2 class="center" style="color: var(--green)">Le bureau de la mutuelle</h2>
      <BureauGrid />
    </div>
  </section>

  <section class="section band-green center">
    <div class="container">
      <h2>Rejoignez la mutuelle</h2>
      <RouterLink to="/inscription" class="btn orange">Devenir membre</RouterLink>
    </div>
  </section>
</template>
