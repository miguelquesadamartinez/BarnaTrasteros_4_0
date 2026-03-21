<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Panel de Control</h1>
    </div>

    <div v-if="loading" class="spinner-wrapper">
      <div class="spinner"></div>
    </div>

    <template v-else>
      <div class="stats-grid">
        <div class="stat-card">
          <span class="stat-label">Trasteros Alquilados</span>
          <span class="stat-value">{{ resumen.trasteros?.alquilados ?? 0 }}</span>
          <span class="stat-sub">de {{ resumen.trasteros?.total ?? 0 }} total · {{ resumen.trasteros?.libres ?? 0 }} libres</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Pisos Alquilados</span>
          <span class="stat-value">{{ resumen.pisos?.alquilados ?? 0 }}</span>
          <span class="stat-sub">de {{ resumen.pisos?.total ?? 0 }} total · {{ resumen.pisos?.libres ?? 0 }} libres</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Pagos Pendientes</span>
          <span class="stat-value">{{ formatMoney(resumen.pagos_pendientes) }}</span>
          <span class="stat-sub">Alquileres por cobrar</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Gastos Pendientes</span>
          <span class="stat-value">{{ formatMoney(resumen.gastos_pendientes) }}</span>
          <span class="stat-sub">Facturas por pagar</span>
        </div>
      </div>

      <!-- Pagos pendientes -->
      <div class="card">
        <div class="card-header" style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
          <h2 class="card-title" style="margin:0;white-space:nowrap">⚠️ Pagos Pendientes</h2>
          <input
            v-model="pendSearch"
            class="form-control"
            style="max-width:260px"
            placeholder="Buscar cliente..."
            @input="onPendSearch"
          />
          <select v-model="pendTipo" class="form-control" style="max-width:140px" @change="loadPendientes(1)">
            <option value="">Todos</option>
            <option value="trastero">Trasteros</option>
            <option value="piso">Pisos</option>
          </select>
          <span class="text-muted" style="white-space:nowrap">{{ pendPagination.total }} registros</span>
        </div>

        <div v-if="pendLoading" class="spinner-wrapper"><div class="spinner"></div></div>
        <div v-else-if="pendError" class="alert alert-danger">{{ pendError }}</div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Cliente</th>
                <th>Mes/Año</th>
                <th>Total</th>
                <th>Pagado</th>
                <th>Pendiente</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="pendPagos.length === 0">
                <td colspan="8" class="text-center text-muted" style="padding:2rem">Sin pagos pendientes</td>
              </tr>
              <tr v-for="p in pendPagos" :key="p.id">
                <td>
                  <span class="badge" :class="p.tipo === 'piso' ? 'badge-primary' : 'badge-info'">
                    {{ p.tipo === 'piso' ? '🏠 Piso' : '📦 Trastero' }}
                  </span>
                </td>
                <td>{{ p.cliente ? `${p.cliente.nombre} ${p.cliente.apellido}` : '—' }}</td>
                <td>{{ mesNombre(p.mes) }} {{ p.anyo }}</td>
                <td>{{ formatMoney(p.importe_total) }}</td>
                <td class="text-success">{{ formatMoney(p.pagado) }}</td>
                <td class="text-danger"><strong>{{ formatMoney(calcPendiente(p)) }}</strong></td>
                <td>
                  <span class="badge" :class="estadoBadge(p.estado)">{{ p.estado }}</span>
                </td>
                <td>
                  <div class="actions-cell">
                    <button
                      class="btn btn-success btn-sm"
                      title="Registrar pago"
                      @click="openPago(p)"
                    >💰 Pagar</button>
                    <button class="btn btn-info btn-sm" title="Ver detalle" @click="openDetalle(p)">📋 Ver</button>
                    <button class="btn btn-secondary btn-sm" title="Imprimir recibo" @click="generarReciboPagoTotal(p)">📄</button>
                  </div>
                </td>
              </tr>
              <tr class="totals-row" v-if="pendPagos.length > 0">
                <td colspan="3" class="text-right"><strong>Totales:</strong></td>
                <td>{{ formatMoney(pendPagos.reduce((s, p) => s + +p.importe_total, 0)) }}</td>
                <td>{{ formatMoney(pendPagos.reduce((s, p) => s + +p.pagado, 0)) }}</td>
                <td>{{ formatMoney(pendPagos.reduce((s, p) => s + calcPendiente(p), 0)) }}</td>
                <td colspan="2"></td>
              </tr>
            </tbody>
          </table>
        </div>

        <AppPagination
          :current-page="pendPage"
          :last-page="pendPagination.last_page"
          :total="pendPagination.total"
          :from="pendPagination.from"
          :to="pendPagination.to"
          @change="loadPendientes"
        />
      </div>
    </template>

    <!-- Modal: Registrar Pago -->
    <AppModal v-model="showPagoModal" title="Registrar Pago" size="md">
      <div v-if="pagoTarget" class="mb-2">
        <p><strong>Cliente:</strong> {{ pagoTarget.cliente?.nombre }} {{ pagoTarget.cliente?.apellido }}</p>
        <p><strong>Pendiente total del cliente:</strong> <span class="text-danger">{{ formatMoney(pendienteTotalClienteHome) }}</span></p>
        <p class="text-muted" style="font-size:.85rem">El pago se distribuirá automáticamente entre los meses más antiguos con deuda (pisos y trasteros). No se puede superar el total pendiente del cliente.</p>
      </div>
      <form @submit.prevent="registrarPago">
        <div class="alert alert-danger" v-if="pagoError">{{ pagoError }}</div>
        <div class="alert alert-success" v-if="pagoSuccess">{{ pagoSuccess }}</div>
        <div class="form-group">
          <label class="form-label">Importe a pagar (€) *</label>
          <input v-model.number="pagoForm.importe" class="form-control" type="number" step="0.01" min="0.01" :max="pendienteTotalClienteHome || undefined" required />
        </div>
        <div class="form-group">
          <label class="form-label">Fecha de pago *</label>
          <input v-model="pagoForm.fecha_pago" class="form-control" type="date" required />
        </div>
        <div class="form-group">
          <label class="form-label">Notas</label>
          <textarea v-model="pagoForm.notas" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="showPagoModal = false">Cerrar</button>
          <button type="submit" class="btn btn-success" :disabled="pagando">
            {{ pagando ? 'Procesando...' : 'Confirmar Pago' }}
          </button>
        </div>
      </form>
    </AppModal>

    <!-- Modal: Detalle de pagos -->
    <AppModal v-model="showDetalle" title="Detalle de Pagos" size="lg">
      <div v-if="detalleTarget">
        <p><strong>{{ detalleTarget.tipo === 'piso' ? '🏠 Piso' : '📦 Trastero' }}</strong> — Ref: {{ detalleTarget.numero ?? detalleTarget.referencia_id }}</p>
        <p>{{ mesNombre(detalleTarget.mes) }} {{ detalleTarget.anyo }} | {{ detalleTarget.cliente?.nombre }} {{ detalleTarget.cliente?.apellido }}</p>
        <div class="detail-table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Importe</th>
                <th>Notas</th>
                <th>Recibo</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!detalleTarget.detalles?.length">
                <td colspan="5" class="text-center text-muted">Sin pagos registrados</td>
              </tr>
              <tr v-for="d in detalleTarget.detalles" :key="d.id">
                <td>{{ formatDate(d.fecha_pago) }}</td>
                <td class="text-success"><strong>{{ formatMoney(d.importe) }}</strong></td>
                <td>{{ d.notas || '—' }}</td>
                <td>
                  <button
                    class="btn btn-secondary btn-sm"
                    title="Descargar recibo PDF"
                    @click="generarReciboPago(detalleTarget, d)"
                  >📄 PDF</button>
                </td>
                <td>
                  <button
                    v-if="deleteDetalleId !== d.id"
                    class="btn btn-danger btn-sm"
                    title="Eliminar este detalle"
                    @click="deleteDetalleId = d.id"
                  >🗑️</button>
                  <span v-else style="display:inline-flex;gap:.35rem;align-items:center">
                    <span style="font-size:.8rem;color:#b00">¿Seguro?</span>
                    <button class="btn btn-danger btn-sm" :disabled="deletingDetalle" @click="doEliminarDetalle(d)">
                      {{ deletingDetalle ? '...' : '✓' }}
                    </button>
                    <button class="btn btn-secondary btn-sm" @click="deleteDetalleId = null">✕</button>
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="detalleError" class="alert alert-danger" style="margin-top:.75rem">{{ detalleError }}</div>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import AppModal from '@/components/AppModal.vue'
import AppPagination from '@/components/AppPagination.vue'
import { usePdfRecibo } from '@/composables/usePdfRecibo'

const { generarReciboPago, generarReciboPagoTotal } = usePdfRecibo()

const resumen = ref({})
const loading = ref(true)

// Pagos pendientes — tabla
const pendPagos      = ref([])
const pendLoading    = ref(false)
const pendError      = ref('')
const pendSearch     = ref('')
const pendTipo       = ref('')
const pendPage       = ref(1)
const pendPagination = ref({ total: 0, last_page: 1, from: 0, to: 0 })

// Modales
const showPagoModal    = ref(false)
const showDetalle      = ref(false)
const pagoTarget       = ref(null)
const detalleTarget    = ref(null)
const pagando          = ref(false)
const pagoError        = ref('')
const pagoSuccess      = ref('')
const pagoForm         = ref({ importe: 0, fecha_pago: today(), notas: '' })
const deleteDetalleId  = ref(null)
const deletingDetalle  = ref(false)
const detalleError     = ref('')

const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']

function today() { return new Date().toISOString().split('T')[0] }
function mesNombre(m) { return MESES[m] || m }
function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}
function formatDate(v) { return v ? v.split('T')[0] : '' }
function calcPendiente(p) {
  return Math.max(0, +p.importe_total - +p.pagado)
}
function estadoBadge(e) {
  return { pendiente: 'badge-danger', parcial: 'badge-warning', pagado: 'badge-success' }[e] || 'badge-muted'
}

const pendienteTotalClienteHome = ref(0)


async function loadPendientes(page = 1) {
  pendPage.value    = page
  pendLoading.value = true
  pendError.value   = ''
  try {
    const params = { page, per_page: 15, estado: 'pendiente,parcial' }
    if (pendSearch.value) params.cliente = pendSearch.value
    if (pendTipo.value)   params.tipo    = pendTipo.value
    const { data } = await api.get('/pagos-alquiler', { params })
    pendPagos.value      = data.data
    pendPagination.value = { total: data.total, last_page: data.last_page, from: data.from, to: data.to }
  } catch (e) {
    pendError.value = 'Error al cargar pagos pendientes'
  } finally {
    pendLoading.value = false
  }
}

let pendTimer = null
function onPendSearch() {
  clearTimeout(pendTimer)
  pendTimer = setTimeout(() => loadPendientes(1), 350)
}

async function openPago(p) {
  pagoTarget.value  = p
  // Total pendiente del cliente en todos sus pagos visibles (pisos + trasteros)
  const totalPendCliente = await api
    .get(`/clientes/${p.cliente_id}/pendiente-total`)
    .then((res) => Number(res.data?.pendiente_total ?? 0))

  pendienteTotalClienteHome.value = totalPendCliente

  pagoForm.value    = { importe: Math.round(totalPendCliente * 100) / 100, fecha_pago: today(), notas: '' }
  pagoError.value   = ''
  pagoSuccess.value = ''
  showPagoModal.value = true
}

function openDetalle(p) {
  detalleTarget.value   = p
  deleteDetalleId.value = null
  detalleError.value    = ''
  showDetalle.value     = true
}

async function registrarPago() {
  pagoError.value   = ''
  pagoSuccess.value = ''
  pagando.value     = true
  try {
    const { data } = await api.post('/pagos-alquiler/registrar-pago', {
      cliente_id:    pagoTarget.value.cliente_id,
      importe:       pagoForm.value.importe,
      fecha_pago:    pagoForm.value.fecha_pago,
      notas:         pagoForm.value.notas,
    })
    pagoSuccess.value = `Pago registrado.${ data.sobrante > 0 ? ` Sobrante: ${formatMoney(data.sobrante)}` : '' }`
    // Actualizar filas afectadas en la tabla
    if (data.pagos_actualizados) {
      data.pagos_actualizados.forEach((updated) => {
        const idx = pendPagos.value.findIndex((p) => p.id === updated.id)
        if (idx !== -1) pendPagos.value[idx] = updated
      })
    }
    // Recargar para reflejar los que ya no son pendientes
    await loadPendientes(pendPage.value)
    showPagoModal.value = false
  } catch (e) {
    pagoError.value = e.response?.data?.error || 'Error al registrar el pago'
  } finally {
    pagando.value = false
  }
}

async function doEliminarDetalle(detalle) {
  deletingDetalle.value = true
  detalleError.value    = ''
  try {
    const { data } = await api.delete(`/pagos-alquiler/${detalleTarget.value.id}/detalles/${detalle.id}`)
    detalleTarget.value = data
    const idx = pendPagos.value.findIndex((p) => p.id === data.id)
    if (idx !== -1) pendPagos.value[idx] = data
    deleteDetalleId.value = null
  } catch (e) {
    detalleError.value = e.response?.data?.error || 'Error al eliminar el detalle'
  } finally {
    deletingDetalle.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/relatorios/resumen-general')
    resumen.value = data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
  loadPendientes(1)
})
</script>
