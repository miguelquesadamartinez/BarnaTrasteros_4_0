<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">📊 Relatorios</h1>
      <button class="btn btn-secondary" @click="reloadAll" :disabled="loading">🔄 Recargar</button>
    </div>

    <!-- Tabs -->
    <div class="card" style="padding:0">
      <div class="tabs-header">
        <button
          v-for="t in tabs"
          :key="t.key"
          class="tab-btn"
          :class="{ active: activeTab === t.key }"
          @click="activeTab = t.key"
        >
          {{ t.label }}
        </button>
      </div>

      <div class="tab-content">
        <div v-if="loading" class="spinner-wrapper"><div class="spinner"></div></div>
        <div v-else-if="error" class="alert alert-danger m-2">{{ error }}</div>

        <!-- TAB: Trasteros -->
        <div v-else-if="activeTab === 'trasteros'">
          <div class="stats-grid stats-3" style="padding:1.5rem 1.5rem 0">
            <div class="stat-card">
              <div class="stat-value">{{ trasteros.total || 0 }}</div>
              <div class="stat-label">Total trasteros</div>
            </div>
            <div class="stat-card stat-success">
              <div class="stat-value">{{ trasteros.alquilados || 0 }}</div>
              <div class="stat-label">Alquilados</div>
            </div>
            <div class="stat-card stat-danger">
              <div class="stat-value">{{ trasteros.libres || 0 }}</div>
              <div class="stat-label">Libres</div>
            </div>
          </div>
          <div class="table-wrapper" style="padding:1.5rem">
            <table>
              <thead>
                <tr>
                  <th>Número</th>
                  <th>Piso</th>
                  <th>Tamaño</th>
                  <th>Precio mensual</th>
                  <th>Estado</th>
                  <th>Cliente</th>
                  <th>Desde</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!trasteros.lista?.length">
                  <td colspan="7" class="text-center text-muted">Sin datos</td>
                </tr>
                <tr v-for="t in trasteros.lista" :key="t.id">
                  <td><strong>{{ t.numero }}</strong></td>
                  <td>{{ t.piso }}</td>
                  <td>{{ t.tamanyo }}</td>
                  <td>{{ formatMoney(t.precio_mensual) }}</td>
                  <td>
                    <span class="badge" :class="t.cliente_id ? 'badge-success' : 'badge-muted'">
                      {{ t.cliente_id ? 'Alquilado' : 'Libre' }}
                    </span>
                  </td>
                  <td>{{ t.cliente ? `${t.cliente.nombre} ${t.cliente.apellido}` : '—' }}</td>
                  <td>{{ formatDate(t.fecha_inicio_alquiler) || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB: Pisos -->
        <div v-else-if="activeTab === 'pisos'">
          <div class="stats-grid stats-3" style="padding:1.5rem 1.5rem 0">
            <div class="stat-card">
              <div class="stat-value">{{ pisos.total || 0 }}</div>
              <div class="stat-label">Total pisos</div>
            </div>
            <div class="stat-card stat-success">
              <div class="stat-value">{{ pisos.alquilados || 0 }}</div>
              <div class="stat-label">Alquilados</div>
            </div>
            <div class="stat-card stat-danger">
              <div class="stat-value">{{ pisos.libres || 0 }}</div>
              <div class="stat-label">Libres</div>
            </div>
          </div>
          <div class="table-wrapper" style="padding:1.5rem">
            <table>
              <thead>
                <tr>
                  <th>Número</th>
                  <th>Piso</th>
                  <th>Precio mensual</th>
                  <th>Estado</th>
                  <th>Cliente</th>
                  <th>Desde</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!pisos.lista?.length">
                  <td colspan="6" class="text-center text-muted">Sin datos</td>
                </tr>
                <tr v-for="p in pisos.lista" :key="p.id">
                  <td><strong>{{ p.numero }}</strong></td>
                  <td>{{ p.piso }}</td>
                  <td>{{ formatMoney(p.precio_mensual) }}</td>
                  <td>
                    <span class="badge" :class="p.cliente_id ? 'badge-success' : 'badge-muted'">
                      {{ p.cliente_id ? 'Alquilado' : 'Libre' }}
                    </span>
                  </td>
                  <td>{{ p.cliente ? `${p.cliente.nombre} ${p.cliente.apellido}` : '—' }}</td>
                  <td>{{ formatDate(p.fecha_inicio_alquiler) || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB: Pagos -->
        <div v-else-if="activeTab === 'pagos'">
          <div class="relatorio-filters" style="padding:1rem 1.5rem 0">
            <div class="filters-bar">
              <div class="filter-item">
                <span class="filter-label">Año</span>
                <input v-model.number="pagosAnyo" class="form-control" type="number" @change="loadPagos" />
              </div>
              <div class="filter-item">
                <span class="filter-label">Mes</span>
                <select v-model="pagosMes" class="form-control" @change="loadPagos">
                  <option value="">Todos</option>
                  <option v-for="m in 12" :key="m" :value="m">{{ mesNombre(m) }}</option>
                </select>
              </div>
            </div>
          </div>
          <div class="stats-grid stats-4" style="padding:1rem 1.5rem 0">
            <div class="stat-card">
              <div class="stat-value">{{ pagos.total || 0 }}</div>
              <div class="stat-label">Registros</div>
            </div>
            <div class="stat-card stat-danger">
              <div class="stat-value">{{ pagos.pendientes || 0 }}</div>
              <div class="stat-label">Pendientes/Parciales</div>
            </div>
            <div class="stat-card stat-success">
              <div class="stat-value">{{ pagos.pagados || 0 }}</div>
              <div class="stat-label">Pagados</div>
            </div>
            <div class="stat-card">
              <div class="stat-value" style="font-size:1.2rem">{{ formatMoney(pagos.total_pendiente) }}</div>
              <div class="stat-label">Total pendiente</div>
            </div>
          </div>
          <div class="table-wrapper" style="padding:1.5rem">
            <table>
              <thead>
                <tr>
                  <th>Tipo</th>
                  <th>Ref.</th>
                  <th>Cliente</th>
                  <th>Mes / Año</th>
                  <th>Total</th>
                  <th>Pagado</th>
                  <th>Pendiente</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!pagos.lista?.length">
                  <td colspan="8" class="text-center text-muted">Sin datos</td>
                </tr>
                <tr v-for="p in pagos.lista" :key="p.id">
                  <td>
                    <span class="badge" :class="p.tipo === 'piso' ? 'badge-primary' : 'badge-info'">
                      {{ p.tipo === 'piso' ? '🏠' : '📦' }} {{ p.tipo }}
                    </span>
                  </td>
                  <td>{{ refNumero(p) }}</td>
                  <td>{{ p.cliente ? `${p.cliente.nombre} ${p.cliente.apellido}` : '—' }}</td>
                  <td>{{ mesNombre(p.mes) }} {{ p.anyo }}</td>
                  <td>{{ formatMoney(p.importe_total) }}</td>
                  <td class="text-success">{{ formatMoney(p.pagado) }}</td>
                  <td :class="pendiente(p) > 0 ? 'text-danger' : ''">{{ formatMoney(pendiente(p)) }}</td>
                  <td><span class="badge" :class="estadoBadge(p.estado)">{{ p.estado }}</span></td>
                </tr>
                <!-- Totales -->
                <tr class="totals-row" v-if="pagos.lista?.length > 0">
                  <td colspan="4" class="text-right"><strong>Totales:</strong></td>
                  <td>{{ formatMoney(pagos.lista.reduce((s, p) => s + +p.importe_total, 0)) }}</td>
                  <td>{{ formatMoney(pagos.lista.reduce((s, p) => s + +p.pagado, 0)) }}</td>
                  <td>{{ formatMoney(pagos.lista.reduce((s, p) => s + pendiente(p), 0)) }}</td>
                  <td></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB: Gastos -->
        <div v-else-if="activeTab === 'gastos'">
          <div class="relatorio-filters" style="padding:1rem 1.5rem 0">
            <div class="filters-bar">
              <div class="filter-item">
                <span class="filter-label">Tipo</span>
                <select v-model="gastosFiltroTipo" class="form-control" @change="loadGastos">
                  <option value="">Todos</option>
                  <option value="agua">Agua</option>
                  <option value="luz">Luz</option>
                  <option value="comunidad">Comunidad</option>
                  <option value="mantenimiento">Mantenimiento</option>
                  <option value="otro">Otro</option>
                </select>
              </div>
            </div>
          </div>
          <div class="stats-grid stats-4" style="padding:1rem 1.5rem 0">
            <div class="stat-card">
              <div class="stat-value">{{ gastos.total || 0 }}</div>
              <div class="stat-label">Total gastos</div>
            </div>
            <div class="stat-card stat-danger">
              <div class="stat-value">{{ gastos.pendientes || 0 }}</div>
              <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-card stat-success">
              <div class="stat-value">{{ gastos.pagados || 0 }}</div>
              <div class="stat-label">Pagados</div>
            </div>
            <div class="stat-card">
              <div class="stat-value" style="font-size:1.2rem">{{ formatMoney(gastos.total_pendiente) }}</div>
              <div class="stat-label">Importe pendiente</div>
            </div>
          </div>
          <div class="table-wrapper" style="padding:1.5rem">
            <table>
              <thead>
                <tr>
                  <th>Tipo</th>
                  <th>Descripción</th>
                  <th>Referencia</th>
                  <th>Emisión</th>
                  <th>Vencimiento</th>
                  <th>Total</th>
                  <th>Pagado</th>
                  <th>Pendiente</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!gastos.lista?.length">
                  <td colspan="9" class="text-center text-muted">Sin datos</td>
                </tr>
                <tr v-for="g in gastos.lista" :key="g.id">
                  <td><span class="badge badge-info">{{ g.tipo }}</span></td>
                  <td>{{ g.descripcion }}</td>
                  <td>
                    <span v-if="g.referencia_tipo !== 'general'" class="text-muted" style="font-size:.85rem">
                      {{ g.referencia_tipo }} #{{ g.referencia_id }}
                    </span>
                    <span v-else>General</span>
                  </td>
                  <td>{{ formatDate(g.fecha_emision) }}</td>
                  <td :class="gastoVencido(g) ? 'text-danger' : ''">{{ formatDate(g.fecha_vencimiento) || '—' }}</td>
                  <td>{{ formatMoney(g.importe_total) }}</td>
                  <td class="text-success">{{ formatMoney(g.pagado) }}</td>
                  <td :class="+g.importe_total - +g.pagado > 0 ? 'text-danger' : ''">
                    {{ formatMoney(+g.importe_total - +g.pagado) }}
                  </td>
                  <td><span class="badge" :class="estadoBadge(g.estado)">{{ g.estado }}</span></td>
                </tr>
                <!-- Totales -->
                <tr class="totals-row" v-if="gastos.lista?.length > 0">
                  <td colspan="5" class="text-right"><strong>Totales:</strong></td>
                  <td>{{ formatMoney(gastos.lista.reduce((s, g) => s + +g.importe_total, 0)) }}</td>
                  <td>{{ formatMoney(gastos.lista.reduce((s, g) => s + +g.pagado, 0)) }}</td>
                  <td>{{ formatMoney(gastos.lista.reduce((s, g) => s + (+g.importe_total - +g.pagado), 0)) }}</td>
                  <td></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const activeTab = ref('trasteros')
const tabs = [
  { key: 'trasteros', label: '📦 Trasteros' },
  { key: 'pisos', label: '🏠 Pisos' },
  { key: 'pagos', label: '💶 Pagos' },
  { key: 'gastos', label: '🧾 Gastos' },
]

const loading = ref(false)
const error = ref('')
const trasteros = ref({})
const pisos = ref({})
const pagos = ref({})
const gastos = ref({})

const pagosAnyo = ref(new Date().getFullYear())
const pagosMes = ref('')
const gastosFiltroTipo = ref('')

const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
function mesNombre(m) { return MESES[m] || m }

function formatDate(v) { return v ? v.split('T')[0] : '' }

function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}

function pendiente(p) { return Math.max(0, +p.importe_total - +p.pagado) }

function refNumero(p) {
  return p.numero ?? p.referencia_id
}

function estadoBadge(e) {
  return { pendiente: 'badge-danger', parcial: 'badge-warning', pagado: 'badge-success' }[e] || 'badge-muted'
}

function gastoVencido(g) {
  return g.fecha_vencimiento && g.estado !== 'pagado' && new Date(g.fecha_vencimiento) < new Date()
}

async function loadTrasteros() {
  const { data } = await api.get('/relatorios/estado-trasteros')
  trasteros.value = data
}

async function loadPisos() {
  const { data } = await api.get('/relatorios/estado-pisos')
  pisos.value = data
}

async function loadPagos() {
  const params = {}
  if (pagosAnyo.value) params.anyo = pagosAnyo.value
  if (pagosMes.value) params.mes = pagosMes.value
  const { data } = await api.get('/relatorios/estado-pagos', { params })
  pagos.value = data
}

async function loadGastos() {
  const params = {}
  if (gastosFiltroTipo.value) params.tipo = gastosFiltroTipo.value
  const { data } = await api.get('/relatorios/estado-gastos', { params })
  gastos.value = data
}

async function reloadAll() {
  loading.value = true
  error.value = ''
  try {
    await Promise.all([loadTrasteros(), loadPisos(), loadPagos(), loadGastos()])
  } catch (e) {
    error.value = e.response?.data?.message || 'Error al cargar los datos'
  } finally {
    loading.value = false
  }
}

onMounted(reloadAll)
</script>

<style scoped>
.tabs-header {
  display: flex;
  border-bottom: 2px solid var(--gris-claro);
  background: #fafafa;
}
.tab-btn {
  padding: 0.8rem 1.4rem;
  border: none;
  background: none;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--gris);
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
}
.tab-btn:hover { color: var(--rojo); }
.tab-btn.active {
  color: var(--rojo);
  border-bottom-color: var(--rojo);
  background: white;
}
.tab-content {
  min-height: 300px;
}
.stats-3 {
  grid-template-columns: repeat(3, 1fr);
}
.stats-4 {
  grid-template-columns: repeat(4, 1fr);
}
</style>
