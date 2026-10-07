<script setup>
import { site } from '../content'
</script>

<template>
  <div class="page-banner"><div class="container"><h1>Faire un don</h1></div></div>
  <section class="section alt">
    <div class="container narrow">
      <div class="card">
        <h2 style="color: var(--green)">Soutenez les actions de la mutuelle</h2>
        <p class="prose">{{ site.don.intro }}</p>
        <p class="alert ok">Le don en ligne (Mobile Money, carte) sera bientôt disponible sur ce site.</p>
        <h3>En attendant</h3>
        <p class="prose">{{ site.don.howTo }}</p>
        <p class="muted">
          Contact : {{ site.contact.phone }} · {{ site.contact.email }}
        </p>
        <RouterLink to="/inscription" class="btn orange">Devenir membre</RouterLink>
      </div>
    </div>
  </section>
</template>
