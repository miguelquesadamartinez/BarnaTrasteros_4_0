import { createRouter, createWebHistory } from 'vue-router'

import HomeView from '@/views/HomeView.vue'
import BusquedaView from '@/views/busqueda/BusquedaView.vue'
import TrasterosView from '@/views/trasteros/TrasterosView.vue'
import PisosView from '@/views/pisos/PisosView.vue'
import ClientesView from '@/views/clientes/ClientesView.vue'
import ListaEsperaView from '@/views/lista-espera/ListaEsperaView.vue'
import AvisarImpagosView from '@/views/contabilidad/AvisarImpagosView.vue'
import FianzasView from '@/views/fianzas/FianzasView.vue'
import PagosView from '@/views/pagos/PagosView.vue'
import GastosView from '@/views/gastos/GastosView.vue'
import RelatoriosView from '@/views/relatorios/RelatoriosView.vue'
import TamanyosTrasterosView from '@/views/mantenimiento/TamanyosTrasterosView.vue'
import FacturasView from '@/views/mantenimiento/FacturasView.vue'
import GenerarPagosView from '@/views/mantenimiento/GenerarPagosView.vue'
import BackupView from '@/views/mantenimiento/BackupView.vue'

const routes = [
  { path: '/', name: 'home', component: HomeView, meta: { title: 'Inicio' } },
  { path: '/busqueda', name: 'busqueda', component: BusquedaView, meta: { title: 'Búsqueda' } },
  { path: '/trasteros', name: 'trasteros', component: TrasterosView, meta: { title: 'Trasteros' } },
  { path: '/pisos', name: 'pisos', component: PisosView, meta: { title: 'Pisos' } },
  { path: '/clientes', name: 'clientes', component: ClientesView, meta: { title: 'Clientes' } },
  { path: '/lista-espera', name: 'lista-espera', component: ListaEsperaView, meta: { title: 'Lista de Espera' } },
  { path: '/fianzas', name: 'fianzas', component: FianzasView, meta: { title: 'Fianzas Activas' }, props: { soloDevueltas: false } },
  { path: '/fianzas/devueltas', name: 'fianzas-devueltas', component: FianzasView, meta: { title: 'Fianzas Devueltas' }, props: { soloDevueltas: true } },
  { path: '/pagos', name: 'pagos', component: PagosView, meta: { title: 'Pagos' } },
  { path: '/gastos', name: 'gastos', component: GastosView, meta: { title: 'Gastos' } },
  { path: '/contabilidad/avisar-impagos', name: 'avisar-impagos', component: AvisarImpagosView, meta: { title: 'Avisar Impagos' } },
  { path: '/relatorios', name: 'relatorios', component: RelatoriosView, meta: { title: 'Relatorios' } },
  { path: '/mantenimiento/tamanyo-trasteros', name: 'tamanyo-trasteros', component: TamanyosTrasterosView, meta: { title: 'Tamaños de Trasteros' } },
  { path: '/mantenimiento/facturas', name: 'facturas', component: FacturasView, meta: { title: 'Facturas del Mes' } },
  { path: '/mantenimiento/generar-pagos', name: 'generar-pagos', component: GenerarPagosView, meta: { title: 'Generar Pagos' } },
  { path: '/mantenimiento/backup', name: 'backup', component: BackupView, meta: { title: 'Backup BD' } },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.afterEach((to) => {
  document.title = `${to.meta.title || 'BarnaTrasteros'} — BarnaTrasteros`
})

export default router
