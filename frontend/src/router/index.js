import { createRouter, createWebHistory } from 'vue-router'

import HomeView from '@/views/HomeView.vue'
import TrasterosView from '@/views/trasteros/TrasterosView.vue'
import PisosView from '@/views/pisos/PisosView.vue'
import ClientesView from '@/views/clientes/ClientesView.vue'
import PagosView from '@/views/pagos/PagosView.vue'
import GastosView from '@/views/gastos/GastosView.vue'
import RelatoriosView from '@/views/relatorios/RelatoriosView.vue'
import TamanyosTrasterosView from '@/views/mantenimiento/TamanyosTrasterosView.vue'

const routes = [
  { path: '/', name: 'home', component: HomeView, meta: { title: 'Inicio' } },
  { path: '/trasteros', name: 'trasteros', component: TrasterosView, meta: { title: 'Trasteros' } },
  { path: '/pisos', name: 'pisos', component: PisosView, meta: { title: 'Pisos' } },
  { path: '/clientes', name: 'clientes', component: ClientesView, meta: { title: 'Clientes' } },
  { path: '/pagos', name: 'pagos', component: PagosView, meta: { title: 'Pagos' } },
  { path: '/gastos', name: 'gastos', component: GastosView, meta: { title: 'Gastos' } },
  { path: '/relatorios', name: 'relatorios', component: RelatoriosView, meta: { title: 'Relatorios' } },
  { path: '/mantenimiento/tamanyo-trasteros', name: 'tamanyo-trasteros', component: TamanyosTrasterosView, meta: { title: 'Tamaños de Trasteros' } },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.afterEach((to) => {
  document.title = `${to.meta.title || 'BarnaTrasteros'} — BarnaTrasteros`
})

export default router
