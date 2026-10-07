import { createRouter, createWebHistory } from 'vue-router'
import { auth, isAdmin } from './auth'

const routes = [
  { path: '/', component: () => import('./views/HomeView.vue') },
  { path: '/la-mutuelle', component: () => import('./views/LaMutuelleView.vue') },
  { path: '/chantiers', component: () => import('./views/NewsListView.vue') },
  { path: '/actualites', component: () => import('./views/NewsListView.vue') },
  { path: '/actualites/:slug', component: () => import('./views/NewsDetailView.vue') },
  { path: '/don', component: () => import('./views/DonView.vue') },
  { path: '/inscription', component: () => import('./views/RegisterView.vue'), meta: { guestOnly: true } },
  { path: '/connexion', component: () => import('./views/LoginView.vue'), meta: { guestOnly: true } },
  { path: '/espace', component: () => import('./views/MemberAreaView.vue'), meta: { requiresAuth: true } },
  { path: '/admin', component: () => import('./views/admin/AdminDashboardView.vue'), meta: { requiresAdmin: true } },
  { path: '/admin/publications', component: () => import('./views/admin/AdminPublicationsView.vue'), meta: { requiresAdmin: true } },
  { path: '/admin/cotisations', component: () => import('./views/admin/AdminCotisationsView.vue'), meta: { requiresAdmin: true } },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, saved) {
    if (saved) return saved
    if (to.hash) return { el: to.hash, top: 16, behavior: 'smooth' }
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
