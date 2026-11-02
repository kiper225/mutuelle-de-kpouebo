<script setup>
import { site } from '../content'
// Seuls les réseaux avec une adresse sont affichés.
const socials = (site.social || []).filter((s) => s.url)
const year = new Date().getFullYear()
</script>

<template>
  <div>
    <footer class="footer">
      <div class="container">
        <div style="flex: 2 1 260px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
          <img src="/logo.jpg" alt="Logo" class="" style="border-radius: 50%;" />
          <div>{{ site.footerText }}</div>
          <div>{{ site.footerRealisation }}</div>
        </div>
        <div>
          <b>Navigation</b><br />
          <RouterLink to="/">Accueil</RouterLink><br />
          <RouterLink to="/la-mutuelle">La mutuelle</RouterLink><br />
          <RouterLink to="/chantiers">Nos chantiers</RouterLink><br />
          <RouterLink to="/actualites">Actualités</RouterLink><br />
          <RouterLink to="/contact">Contact</RouterLink>
        </div>
        <div>
          <b>Découvrir la mutuelle</b><br />
          <RouterLink to="/la-mutuelle#president">Mot du président</RouterLink><br />
          <RouterLink to="/la-mutuelle#histoire">Histoire</RouterLink><br />
          <RouterLink to="/la-mutuelle#bureau">Le bureau</RouterLink><br />
          <RouterLink to="/chantiers?category=projet_en_cours">Nos chantiers</RouterLink>
        </div>
        <div>
          <b>Rejoignez-nous</b><br />
          <RouterLink to="/inscription">Adhérer</RouterLink><br />
          <RouterLink to="/espace">Payer ma cotisation</RouterLink><br />
          <RouterLink to="/don">Faire un don</RouterLink><br />
          <RouterLink to="/espace">Espace membres</RouterLink>
        </div>
        <div>
          <b>Contact</b><br />
          {{ site.contact.phone }}<br />
          {{ site.contact.email }}<br />
          {{ site.contact.address }}
        </div>
      </div>
      <div class="container">
        <div style="flex: 2 1 260px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
        </div>
        <div>
          <b>FAQ</b><br />
          <RouterLink to="" target="_blank" rel="noopener noreferrer">Comment ça marche ?</RouterLink><br />
          <RouterLink to="" target="_blank" rel="noopener noreferrer">Comment faire un don ?</RouterLink><br />
          <RouterLink to="" target="_blank" rel="noopener noreferrer">Comment devenir membre ?</RouterLink><br />
          <RouterLink to="" target="_blank" rel="noopener noreferrer">Comment nous contacter ?</RouterLink>
        </div>
        <div>
          <b>Suivez-nous</b><br />
          <div v-if="socials.length" style="margin-top: 10px">
            <a v-for="s in socials" :key="s.label" :href="s.url" target="_blank" rel="noopener noreferrer" style="margin-right: 12px">{{ s.label }}</a>
          </div>
        </div>
      </div>
    </footer>
    <div class="copyright">
      <div class="container">© {{ year }} {{ site.name }}. Tous droits réservés_____________________________________________________________________________________________________________________ Développé par  : <a href="https://github.com/kiper225" target="_blank" rel="noopener noreferrer">GERVAIS KIPRE</a></div>
    </div>
  </div>
</template>
