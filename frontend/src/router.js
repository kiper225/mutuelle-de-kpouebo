import { createRouter, createWebHistory } from 'vue-router'
import { auth, isAdmin } from './auth'

const routes = [
  { path: '/', component: () => import('./views/HomeView.vue') },
  { path: '/la-mutuelle', component: () => import('./views/LaMutuelleView.vue') },
  { path: '/chantiers', component: () => import('./views/NewsListView.vue') },
  { path: '/actualites', component: () => import('./views/NewsListView.vue') },
  { path: '/actualites/:slug', component: () => import('./views/NewsDetailView.vue') },
  { path: '/don', component: () => import('./views/DonView.vue') },
  { path: '/paiement/retour', component: () => import('./views/PaymentReturnView.vue') },
  { path: '/inscription', component: () => import('./views/RegisterView.vue'), meta: { guestOnly: true } },
  { path: '/connexion', component: () => import('./views/LoginView.vue'), meta: { guestOnly: true } },
  { path: '/espace', component: () => import('./views/MemberAreaView.vue'), meta: { requiresAuth: true } },
  { path: '/profil', component: () => import('./views/ProfileView.vue'), meta: { requiresAuth: true } },
  { path: '/messagerie', component: () => import('./views/MessagerieView.vue'), meta: { requiresAuth: true, layout: 'app' } },
  { path: '/imprimer/:type/:id', component: () => import('./views/PrintView.vue'), meta: { requiresAdmin: true } },
  {
    path: '/admin',
    component: () => import('./layouts/AdminLayout.vue'),
    meta: { requiresAdmin: true, layout: 'admin' },
    children: [
      {
        path: '',
        component: () => import('./views/admin/AdminDashboardView.vue'),
        meta: { title: 'Tableau de bord', subtitle: 'Vue d’ensemble des membres, cotisations et dons.' },
      },
      {
        path: 'membres',
        component: () => import('./views/admin/AdminMembersView.vue'),
        meta: { title: 'Membres', subtitle: 'Validez les adhésions et gérez les dossiers.' },
      },
      {
        path: 'membres/:id',
        component: () => import('./views/admin/AdminMemberEditView.vue'),
        meta: { title: 'Dossier du membre', subtitle: 'Informations, photo et documents à imprimer.' },
      },
      {
        path: 'publications',
        component: () => import('./views/admin/AdminPublicationsView.vue'),
        meta: { title: 'Publications', subtitle: 'Créez et modifiez les actualités et les projets.' },
      },
      {
        path: 'cotisations',
        component: () => import('./views/admin/AdminCotisationsView.vue'),
        meta: { title: 'Cotisations', subtitle: 'Suivi mensuel et paiements hors ligne.' },
      },
      {
        path: 'donations',
        component: () => import('./views/admin/AdminDonationsView.vue'),
        meta: { title: 'Dons', subtitle: 'Dons reçus en ligne et hors ligne.' },
      },
    ],
  },
  { path: '/recherche', component: () => import('./views/SearchView.vue') },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, saved) {
    if (saved) return saved
    if (to.hash) return { el: to.hash, top: 170, behavior: 'smooth' }
    return { top: 0 }
  },
})

router.beforeEach((to) => {
  const logged = !!auth.user
  if ((to.meta.requiresAuth || to.meta.requiresAdmin) && !logged) {
    return { path: '/connexion', query: { redirect: to.fullPath } }
  }
  if (to.meta.requiresAdmin && !isAdmin.value) return '/espace'
  if (to.meta.guestOnly && logged) return isAdmin.value ? '/admin' : '/espace'
})

export default router