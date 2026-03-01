<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">💶 Gestión de Pagos</h1>
      <button class="btn btn-primary" @click="openNewPago">+ Crear Registro de Pago</button>
    </div>

    <!-- Filtros -->
    <div class="card">
      <div class="filters-bar">
        <div class="filter-item">
          <span class="filter-label">Tipo</span>
          <select v-model="filters.tipo" class="form-control" @change="loadPagos(1)">
            <option value="">Todos</option>
            <option value="trastero">Trasteros</option>
            <option value="piso">Pisos</option>
          </select>
        </div>
        <div class="filter-item">
          <span class="filter-label">Estado</span>
          <select v-model="filters.estado" class="form-control" @change="loadPagos(1)">
            <option value="">Todos</option>
            <option value="pendiente">Pendiente</option>
            <option value="parcial">Parcial</option>
            <option value="pagado">Pagado</option>
          </select>
        </div>
        <div class="filter-item">
          <span class="filter-label">Año</span>
          <input v-model="filters.anyo" class="form-control" type="number" placeholder="Año" @change="loadPagos(1)" />
        </div>
        <div class="filter-item">
          <span class="filter-label">Mes</span>
          <select v-model="filters.mes" class="form-control" @change="loadPagos(1)">
            <option value="">Todos</option>
            <option v-for="m in 12" :key="m" :value="m">{{ mesNombre(m) }}</option>
          </select>
        </div>
        <button class="btn btn-secondary" style="margin-top:auto" @click="clearFilters">Limpiar</button>
      </div>

      <div v-if="store.loading" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="store.error" class="alert alert-danger">{{ store.error }}</div>
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
            <tr v-if="store.pagos.length === 0">
              <td colspan="9" class="text-center text-muted" style="padding:2rem">Sin registros</td>
            </tr>
            <tr v-for="p in store.pagos" :key="p.id">
              <td>
                <span class="badge" :class="p.tipo === 'piso' ? 'badge-primary' : 'badge-info'">
                  {{ p.tipo === 'piso' ? '🏠 Piso' : '📦 Trastero' }}
                </span>
              </td>
              <td>{{ p.cliente ? `${p.cliente.nombre} ${p.cliente.apellido}` : '—' }}</td>
              <td>{{ mesNombre(p.mes) }} {{ p.anyo }}</td>
              <td>{{ formatMoney(p.importe_total) }}</td>
              <td class="text-success">{{ formatMoney(p.pagado) }}</td>
              <td :class="pendiente(p) > 0 ? 'text-danger' : ''">
                {{ formatMoney(pendiente(p)) }}
              </td>
              <td>
                <span class="badge" :class="estadoBadge(p.estado)">{{ p.estado }}</span>
              </td>
              <td>
                <div class="actions-cell">
                  <button
                    v-if="p.estado !== 'pagado'"
                    class="btn btn-success btn-sm"
                    title="Registrar pago"
                    @click="openPago(p)"
                  >💰 Pagar</button>
                  <button class="btn btn-info btn-sm" title="Ver detalle del pago" @click="openDetalle(p)">📋 Ver</button>
                  <button class="btn btn-secondary btn-sm" title="Imprimir recibo" @click="generarReciboPagoTotal(p)">📄</button>
                  <button class="btn btn-danger btn-sm" title="Eliminar pago" @click="confirmDelete(p)">🗑️</button>
                </div>
              </td>
            </tr>
            <!-- Totales -->
            <tr class="totals-row" v-if="store.pagos.length > 0">
              <td colspan="4" class="text-right"><strong>Totales:</strong></td>
              <td>{{ formatMoney(store.pagos.reduce((s, p) => s + +p.importe_total, 0)) }}</td>
              <td>{{ formatMoney(store.pagos.reduce((s, p) => s + +p.pagado, 0)) }}</td>
              <td>{{ formatMoney(store.pagos.reduce((s, p) => s + pendiente(p), 0)) }}</td>
              <td colspan="2"></td>
            </tr>
          </tbody>
        </table>
      </div>
      <AppPagination
        :current-page="store.pagination.current_page"
        :last-page="store.pagination.last_page"
        :total="store.pagination.total"
        :from="store.pagination.from"
        :to="store.pagination.to"
        @change="onPageChange"
      />
    </div>

    <!-- Modal: Registrar Pago -->
    <AppModal v-model="showPagoModal" title="Registrar Pago" size="md">
      <div v-if="pagoTarget" class="mb-2">
        <p><strong>Tipo:</strong> {{ pagoTarget.tipo }} | <strong>Ref:</strong> {{ pagoTarget.referencia_id }}</p>
        <p><strong>Pendiente total del cliente:</strong> <span class="text-danger">{{ formatMoney(pendienteTotalCliente) }}</span></p>
        <p class="text-muted" style="font-size:.85rem">El pago se distribuirá automáticamente entre los meses más antiguos con deuda.</p>
      </div>
      <form @submit.prevent="registrarPago">
        <div class="alert alert-danger" v-if="pagoError">{{ pagoError }}</div>
        <div class="alert alert-success" v-if="pagoSuccess">{{ pagoSuccess }}</div>
        <div class="form-group">
          <label class="form-label">Importe a pagar (€) *</label>
          <input v-model.number="pagoForm.importe" class="form-control" type="number" step="0.01" min="0.01" required />
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
        <p><strong>{{ detalleTarget.tipo === 'piso' ? '🏠 Piso' : '📦 Trastero' }}</strong> — Ref: {{ detalleTarget.referencia_id }}</p>
        <p>{{ mesNombre(detalleTarget.mes) }} {{ detalleTarget.anyo }} | {{ detalleTarget.cliente?.nombre }} {{ detalleTarget.cliente?.apellido }}</p>
        <div class="detail-table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Importe</th>
                <th>Notas</th>
                <th>Recibo</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!detalleTarget.detalles?.length">
                <td colspan="4" class="text-center text-muted">Sin pagos registrados</td>
              </tr>
              <tr v-for="d in detalleTarget.detalles" :key="d.id">
                <td>{{ formatDate(d.fecha_pago) }}</td>
                <td class="text-success"><strong>{{ formatMoney(d.importe) }}</strong></td>
                <td>{{ d.notas || '—' }}</td>
                <td>
                  <button
                    class="btn btn-secondary btn-sm"
                    title="Descargar recibo PDF"
                    @click="descargarReciboPago(detalleTarget, d)"
                  >📄 PDF</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </AppModal>

    <!-- Modal: Nuevo registro manual -->
    <AppModal v-model="showNewModal" title="Crear Registro de Pago Manual" size="md">
      <form @submit.prevent="crearRegistro">
        <div class="alert alert-danger" v-if="newError">{{ newError }}</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tipo *</label>
            <select v-model="newForm.tipo" class="form-control" required>
              <option value="">Selecciona...</option>
              <option value="trastero">Trastero</option>
              <option value="piso">Piso</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Referencia (ID) *</label>
            <SearchSelect
              v-model="newForm.referencia_id"
              :options="newForm.tipo === 'trastero' ? trasteroOptions : pisoOptions"
              placeholder="Buscar..."
            />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Cliente *</label>
          <SearchSelect v-model="newForm.cliente_id" :options="clienteOptions" placeholder="Buscar cliente..." />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Mes *</label>
            <select v-model="newForm.mes" class="form-control" required>
              <option v-for="m in 12" :key="m" :value="m">{{ mesNombre(m) }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Año *</label>
            <input v-model.number="newForm.anyo" class="form-control" type="number" required />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Importe total (€) *</label>
          <input v-model.number="newForm.importe_total" class="form-control" type="number" step="0.01" min="0" required />
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="showNewModal = false">Cancelar</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Guardando...' : 'Crear' }}
          </button>
        </div>
      </form>
    </AppModal>

    <!-- Confirm delete -->
    <AppModal v-model="showDelete" title="Confirmar eliminación" size="sm">
      <p>¿Eliminar este registro de pago?</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showDelete = false">Cancelar</button>
        <button class="btn btn-danger" @click="doDelete" :disabled="saving">Eliminar</button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePagosStore } from '@/stores/pagos'
import { useClientesStore } from '@/stores/clientes'
import { useTrasterosStore } from '@/stores/trasteros'
import { usePisosStore } from '@/stores/pisos'
import AppModal from '@/components/AppModal.vue'
import AppPagination from '@/components/AppPagination.vue'
import SearchSelect from '@/components/SearchSelect.vue'
import { usePdfRecibo } from '@/composables/usePdfRecibo'

const store = usePagosStore()
const clientesStore = useClientesStore()
const trasterosStore = useTrasterosStore()
const pisosStore = usePisosStore()
const { generarReciboPago, generarReciboPagoTotal } = usePdfRecibo()

function descargarReciboPago(pago, detalle) {
  generarReciboPago(pago, detalle)
}

const filters = ref({ tipo: '', estado: '', anyo: new Date().getFullYear(), mes: '' })
const currentPage = ref(1)
const showPagoModal = ref(false)
const showDetalle = ref(false)
const showNewModal = ref(false)
const showDelete = ref(false)
const pagoTarget = ref(null)
const detalleTarget = ref(null)
const toDelete = ref(null)
const pagando = ref(false)
const saving = ref(false)
const pagoError = ref('')
const pagoSuccess = ref('')
const newError = ref('')

const pagoForm = ref({ importe: 0, fecha_pago: today(), notas: '' })
const newForm = ref({ tipo: 'trastero', referencia_id: null, cliente_id: null, mes: new Date().getMonth() + 1, anyo: new Date().getFullYear(), importe_total: 0 })

function today() {
  return new Date().toISOString().split('T')[0]
}

function formatDate(v) { return v ? v.split('T')[0] : '' }

function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}

function pendiente(p) {
  return Math.max(0, +p.importe_total - +p.pagado)
}

const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
function mesNombre(m) { return MESES[m] || m }

function estadoBadge(e) {
  return { pendiente: 'badge-danger', parcial: 'badge-warning', pagado: 'badge-success' }[e] || 'badge-muted'
}

const pendienteTotalCliente = computed(() => {
  if (!pagoTarget.value) return 0
  return store.pagos
    .filter(
      (p) =>
        p.tipo === pagoTarget.value.tipo &&
        p.referencia_id === pagoTarget.value.referencia_id &&
        ['pendiente', 'parcial'].includes(p.estado)
    )
    .reduce((s, p) => s + pendiente(p), 0)
})

const clienteOptions = computed(() =>
  clientesStore.clientes.map((c) => ({ value: c.id, label: `${c.nombre} ${c.apellido} — ${c.dni}` }))
)
const trasteroOptions = computed(() =>
  trasterosStore.trasteros.map((t) => ({ value: t.id, label: `${t.numero} — ${t.piso}` }))
)
const pisoOptions = computed(() =>
  pisosStore.pisos.map((p) => ({ value: p.id, label: `${p.numero} — ${p.piso}` }))
)

async function loadPagos(page = currentPage.value) {
  currentPage.value = page
  const params = { page }
  if (filters.value.tipo) params.tipo = filters.value.tipo
  if (filters.value.estado) params.estado = filters.value.estado
  if (filters.value.anyo) params.anyo = filters.value.anyo
  if (filters.value.mes) params.mes = filters.value.mes
  await store.fetchPagos(params)
}

function onPageChange(page) {
  loadPagos(page)
}

function clearFilters() {
  filters.value = { tipo: '', estado: '', anyo: new Date().getFullYear(), mes: '' }
  currentPage.value = 1
  loadPagos(1)
}

function openPago(p) {
  pagoTarget.value = p
  pagoForm.value = { importe: pendiente(p), fecha_pago: today(), notas: '' }
  pagoError.value = ''
  pagoSuccess.value = ''
  showPagoModal.value = true
}

function openDetalle(p) {
  detalleTarget.value = p
  showDetalle.value = true
}

function openNewPago() {
  newForm.value = { tipo: 'trastero', referencia_id: null, cliente_id: null, mes: new Date().getMonth() + 1, anyo: new Date().getFullYear(), importe_total: 0 }
  newError.value = ''
  showNewModal.value = true
}

function confirmDelete(p) {
  toDelete.value = p
  showDelete.value = true
}

async function registrarPago() {
  pagoError.value = ''
  pagoSuccess.value = ''
  pagando.value = true
  try {
    const result = await store.registrarPago({
      tipo: pagoTarget.value.tipo,
      referencia_id: pagoTarget.value.referencia_id,
      importe: pagoForm.value.importe,
      fecha_pago: pagoForm.value.fecha_pago,
      notas: pagoForm.value.notas,
    })
    pagoSuccess.value = `Pago registrado. ${result.sobrante > 0 ? `Sobrante no aplicado: ${formatMoney(result.sobrante)}` : ''}`
    await loadPagos(currentPage.value)
  } catch (e) {
    pagoError.value = e.displayMessage || 'Error al registrar el pago'
  } finally {
    pagando.value = false
  }
}

async function crearRegistro() {
  newError.value = ''
  saving.value = true
  try {
    await store.crearPago({ ...newForm.value })
    showNewModal.value = false
    await loadPagos(1)
  } catch (e) {
    newError.value = e.displayMessage || 'Error al crear el registro'
  } finally {
    saving.value = false
  }
}

async function doDelete() {
  saving.value = true
  try {
    await store.deletePago(toDelete.value.id)
    showDelete.value = false
    await loadPagos(currentPage.value)
  } catch (e) {
    alert(e.displayMessage || 'Error al eliminar')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadPagos(1)
  clientesStore.fetchClientes({ page: 1, per_page: 1000 })
  trasterosStore.fetchTrasteros()
  pisosStore.fetchPisos()
})
</script>
