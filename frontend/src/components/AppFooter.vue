<script setup>
import { site } from '../content'
import LanguageSwitcher from './LanguageSwitcher.vue'

const year = new Date().getFullYear()
// Seuls les réseaux avec une adresse sont affichés.
const socials = (site.social || []).filter((s) => s.url)
</script>

<template>
  <div>
    <footer class="footer">
      <div class="container">
        <div style="flex: 2 1 260px">
          <img src="/logo.jpg" alt="Logo" />
          <div>{{ site.name }} : {{ site.footerText }}</div>
          <div style="margin-top: 12px"><LanguageSwitcher /></div>
        </div>
        <div>
          <b>{{ $t('footer.discover') }}</b><br />
          <RouterLink to="/la-mutuelle#president">{{ $t('footer.president') }}</RouterLink><br />
          <RouterLink to="/la-mutuelle#histoire">{{ $t('footer.history') }}</RouterLink><br />
          <RouterLink to="/la-mutuelle#bureau">{{ $t('footer.board') }}</RouterLink><br />
          <RouterLink to="/chantiers">{{ $t('footer.projects') }}</RouterLink>
        </div>
        <div>
          <b>{{ $t('footer.join') }}</b><br />
          <RouterLink to="/inscription">{{ $t('footer.apply') }}</RouterLink><br />
          <RouterLink to="/espace">{{ $t('footer.payDues') }}</RouterLink><br />
          <RouterLink to="/don">{{ $t('footer.donate') }}</RouterLink><br />
          <RouterLink to="/collectes">{{ $t('footer.fundraisers') }}</RouterLink>
        </div>
        <div>
          <b>{{ $t('footer.contact') }}</b><br />
          {{ site.contact.phone }}<br />
          {{ site.contact.email }}<br />
          {{ site.contact.address }}
          <div v-if="socials.length" style="margin-top: 10px">
            <a v-for="s in socials" :key="s.label" :href="s.url" target="_blank" rel="noopener noreferrer" style="margin-right: 12px">{{ s.label }}</a>
          </div>
        </div>
      </div>
    </footer>
    <div class="copyright">
      <div class="container">{{ $t('footer.rights', { year, name: site.name }) }}</div>
    </div>
  </div>
</template>