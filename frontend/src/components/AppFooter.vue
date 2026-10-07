<script setup>
import { site } from '../content'
const year = new Date().getFullYear()
</script>

<template>
  <div>
    <footer class="footer">
      <div class="container">
        <div style="flex: 2 1 260px">
          <img src="/logo.jpg" alt="Logo" />
          <div>{{ site.name }} : {{ site.footerText }}</div>
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
    </footer>
    <div class="copyright">
      <div class="container">© {{ year }} {{ site.name }}. Tous droits réservés. Développé par <a href="https://github.com/kiper225" target="_blank" rel="noopener noreferrer">GERVAIS KIPRE</a></div>
    </div>
  </div>
</template>
