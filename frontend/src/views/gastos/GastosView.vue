<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">🧾 Gestión de Gastos</h1>
      <button class="btn btn-primary" @click="openNew">+ Nuevo Gasto</button>
    </div>

    <!-- Filtros -->
    <div class="card">
      <div class="filters-bar">
        <div class="filter-item">
          <span class="filter-label">Tipo</span>
          <select v-model="filters.tipo" class="form-control" @change="loadGastos(1)">
            <option value="">Todos</option>
            <option value="agua">Agua</option>
            <option value="luz">Luz</option>
            <option value="comunidad">Comunidad</option>
            <option value="mantenimiento">Mantenimiento</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="filter-item">
          <span class="filter-label">Estado</span>
          <select v-model="filters.estado" class="form-control" @change="loadGastos(1)">
            <option value="">Todos</option>
            <option value="pendiente">Pendiente</option>
            <option value="parcial">Parcial</option>
            <option value="pagado">Pagado</option>
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
              <th>Descripción</th>
              <th>Referencia</th>
              <th>Emisión</th>
              <th>Vencimiento</th>
              <th>Total</th>
              <th>Pagado</th>
              <th>Pendiente</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.gastos.length === 0">
              <td colspan="10" class="text-center text-muted" style="padding:2rem">Sin gastos registrados</td>
            </tr>
            <tr v-for="g in store.gastos" :key="g.id">
              <td><span class="badge badge-info">{{ tipoLabel(g.tipo) }}</span></td>
              <td>{{ g.descripcion }}</td>
              <td>
                <span v-if="g.referencia_tipo !== 'general'" class="text-muted" style="font-size:.85rem">
                  {{ g.referencia_tipo }} #{{ g.referencia_id }}
                </span>
                <span v-else class="badge badge-muted">General</span>
              </td>
              <td>{{ formatDate(g.fecha_emision) }}</td>
              <td :class="vencido(g) ? 'text-danger' : ''">{{ formatDate(g.fecha_vencimiento) || '—' }}</td>
              <td>{{ formatMoney(g.importe_total) }}</td>
              <td class="text-success">{{ formatMoney(g.pagado) }}</td>
              <td :class="+g.importe_total - +g.pagado > 0 ? 'text-danger' : ''">
                {{ formatMoney(+g.importe_total - +g.pagado) }}
              </td>
              <td><span class="badge" :class="estadoBadge(g.estado)">{{ g.estado }}</span></td>
              <td>
                <div class="actions-cell">
                  <button v-if="g.estado !== 'pagado'" class="btn btn-success btn-sm" title="Registrar pago" @click="openPago(g)">💰</button>
                  <button class="btn btn-secondary btn-sm" title="Imprimir recibo" @click="generarReciboGastoTotal(g)">📄</button>
                  <button class="btn btn-secondary btn-sm" title="Ver imágenes" @click="openImagenes(g)">🖼️</button>
                  <button class="btn btn-info btn-sm" title="Editar gasto" @click="openEdit(g)">✏️</button>
                  <button class="btn btn-danger btn-sm" title="Eliminar gasto" @click="confirmDelete(g)">🗑️</button>
                </div>
              </td>
            </tr>
            <!-- Totales -->
            <tr class="totals-row" v-if="store.gastos.length > 0">
              <td colspan="5" class="text-right"><strong>Totales:</strong></td>
              <td>{{ formatMoney(store.gastos.reduce((s, g) => s + +g.importe_total, 0)) }}</td>
              <td>{{ formatMoney(store.gastos.reduce((s, g) => s + +g.pagado, 0)) }}</td>
              <td>{{ formatMoney(store.gastos.reduce((s, g) => s + (+g.importe_total - +g.pagado), 0)) }}</td>
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

    <!-- Modal: Crear/Editar gasto -->
    <AppModal v-model="showForm" :title="editTarget ? 'Editar Gasto' : 'Nuevo Gasto'" size="lg">
      <form @submit.prevent="saveGasto">
        <div class="alert alert-danger" v-if="formError">{{ formError }}</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tipo *</label>
            <select v-model="form.tipo" class="form-control" required>
              <option value="">Selecciona...</option>
              <option value="agua">💧 Agua</option>
              <option value="luz">⚡ Luz</option>
              <option value="comunidad">🏘️ Comunidad</option>
              <option value="mantenimiento">🔧 Mantenimiento</option>
              <option value="otro">📄 Otro</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Referencia</label>
            <select v-model="form.referencia_tipo" class="form-control">
              <option value="general">General</option>
              <option value="piso">Piso</option>
              <option value="trastero">Trastero</option>
            </select>
          </div>
        </div>
        <div class="form-group" v-if="form.referencia_tipo !== 'general'">
          <label class="form-label">{{ form.referencia_tipo === 'piso' ? 'Piso' : 'Trastero' }} *</label>
          <SearchSelect
            v-model="form.referencia_id"
            :options="form.referencia_tipo === 'piso' ? pisoOptions : trasteroOptions"
            placeholder="Buscar..."
          />
        </div>
        <div class="form-group">
          <label class="form-label">Descripción *</label>
          <input v-model="form.descripcion" class="form-control" type="text" required />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Fecha emisión *</label>
            <input v-model="form.fecha_emision" class="form-control" type="date" required />
          </div>
          <div class="form-group">
            <label class="form-label">Fecha vencimiento</label>
            <input v-model="form.fecha_vencimiento" class="form-control" type="date" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Importe total (€) *</label>
          <input v-model.number="form.importe_total" class="form-control" type="number" step="0.01" min="0" required />
        </div>
        <!-- Pagos realizados (solo edición) -->
        <div v-if="editTarget && editTarget.detalles && editTarget.detalles.length > 0" class="pagos-realizados">
          <h4 class="pagos-realizados-title">Pagos realizados</h4>
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
                <tr v-for="d in editTarget.detalles" :key="d.id">
                  <td>{{ formatDate(d.fecha_pago) }}</td>
                  <td class="text-success"><strong>{{ formatMoney(d.importe) }}</strong></td>
                  <td>{{ d.notas || '—' }}</td>
                  <td>
                    <button
                      class="btn btn-secondary btn-sm"
                      title="Descargar recibo PDF"
                      @click="descargarReciboGasto(editTarget, d)"
                    >📄 PDF</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="showForm = false">Cancelar</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Guardando...' : editTarget ? 'Guardar cambios' : 'Crear gasto' }}
          </button>
        </div>
      </form>
    </AppModal>

    <!-- Modal: Registrar pago de gasto -->
    <AppModal v-model="showPagoModal" title="Registrar Pago del Gasto" size="md">
      <div v-if="pagoTarget" class="mb-2">
        <p><strong>Gasto:</strong> {{ pagoTarget.descripcion }}</p>
        <p><strong>Pendiente:</strong> <span class="text-danger">{{ formatMoney(+pagoTarget.importe_total - +pagoTarget.pagado) }}</span></p>
      </div>
      <form @submit.prevent="registrarPagoGasto">
        <div class="alert alert-danger" v-if="pagoError">{{ pagoError }}</div>
        <div class="form-group">
          <label class="form-label">Importe (€) *</label>
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

    <!-- Modal: Imágenes del gasto -->
    <AppModal v-model="showImagenesModal" title="Imágenes del Gasto" size="xl">
      <div v-if="imagenTarget">
        <div class="imagenes-grid" v-if="imagenTarget.imagenes && imagenTarget.imagenes.length > 0">
          <div
            class="imagen-item"
            v-for="img in imagenTarget.imagenes"
            :key="img.id"
          >
            <img :src="apiImageUrl(img.ruta)" :alt="img.nombre_original" />
            <div class="imagen-name">{{ img.nombre_original }}</div>
            <button class="btn btn-danger btn-sm" @click="eliminarImg(img.id)">🗑️ Eliminar</button>
          </div>
        </div>
        <p v-else class="text-muted text-center">Sin imágenes adjuntas</p>
        <hr />
        <h4>Subir nuevas imágenes</h4>
        <div class="form-group">
          <input type="file" multiple accept="image/*" @change="onFilesChange" class="form-control" />
        </div>
        <div class="form-actions">
          <button class="btn btn-primary" :disabled="!filesToUpload.length || uploading" @click="subirImagenes">
            {{ uploading ? 'Subiendo...' : `Subir ${filesToUpload.length} imagen(es)` }}
          </button>
        </div>
      </div>
    </AppModal>

    <!-- Confirm delete -->
    <AppModal v-model="showDelete" title="Confirmar eliminación" size="sm">
      <p>¿Eliminar el gasto "<strong>{{ toDelete?.descripcion }}</strong>"?</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showDelete = false">Cancelar</button>
        <button class="btn btn-danger" @click="doDelete" :disabled="saving">Eliminar</button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useGastosStore } from '@/stores/gastos'
import { useTrasterosStore } from '@/stores/trasteros'
import { usePisosStore } from '@/stores/pisos'
import AppModal from '@/components/AppModal.vue'
import AppPagination from '@/components/AppPagination.vue'
import SearchSelect from '@/components/SearchSelect.vue'
import { usePdfRecibo } from '@/composables/usePdfRecibo'

const store = useGastosStore()
const trasterosStore = useTrasterosStore()
const pisosStore = usePisosStore()
const { generarReciboGasto, generarReciboGastoTotal } = usePdfRecibo()

function descargarReciboGasto(gasto, detalle) {
  generarReciboGasto(gasto, detalle)
}

const filters = ref({ tipo: '', estado: '' })
const currentPage = ref(1)
const showForm = ref(false)
const showPagoModal = ref(false)
const showImagenesModal = ref(false)
const showDelete = ref(false)
const editTarget = ref(null)
const pagoTarget = ref(null)
const imagenTarget = ref(null)
const toDelete = ref(null)
const saving = ref(false)
const pagando = ref(false)
const uploading = ref(false)
const formError = ref('')
const pagoError = ref('')
const filesToUpload = ref([])

const TIPOS = { agua: '💧 Agua', luz: '⚡ Luz', comunidad: '🏘️ Comunidad', mantenimiento: '🔧 Mantenimiento', otro: '📄 Otro' }
function tipoLabel(t) { return TIPOS[t] || t }

const defaultForm = () => ({
  tipo: '',
  descripcion: '',
  referencia_tipo: 'general',
  referencia_id: null,
  fecha_emision: today(),
  fecha_vencimiento: '',
  importe_total: 0,
})

const form = ref(defaultForm())
const pagoForm = ref({ importe: 0, fecha_pago: today(), notas: '' })

function today() { return new Date().toISOString().split('T')[0] }
function formatDate(v) { return v ? v.split('T')[0] : '' }
function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}
function estadoBadge(e) {
  return { pendiente: 'badge-danger', parcial: 'badge-warning', pagado: 'badge-success' }[e] || 'badge-muted'
}
function vencido(g) {
  return g.fecha_vencimiento && g.estado !== 'pagado' && new Date(g.fecha_vencimiento) < new Date()
}
function apiImageUrl(ruta) {
  const base = import.meta.env.VITE_API_BASE_URL?.replace('/api', '') || 'http://localhost:8000'
  return `${base}/storage/${ruta}`
}

const trasteroOptions = computed(() =>
  trasterosStore.trasteros.map((t) => ({ value: t.id, label: `${t.numero} — ${t.piso}` }))
)
const pisoOptions = computed(() =>
  pisosStore.pisos.map((p) => ({ value: p.id, label: `${p.numero} — ${p.piso}` }))
)

async function loadGastos(page = currentPage.value) {
  currentPage.value = page
  const params = { page }
  if (filters.value.tipo) params.tipo = filters.value.tipo
  if (filters.value.estado) params.estado = filters.value.estado
  await store.fetchGastos(params)
}

function onPageChange(page) {
  loadGastos(page)
}

function clearFilters() {
  filters.value = { tipo: '', estado: '' }
  currentPage.value = 1
  loadGastos(1)
}

function openNew() {
  editTarget.value = null
  form.value = defaultForm()
  formError.value = ''
  showForm.value = true
}

function openEdit(g) {
  editTarget.value = g
  form.value = {
    tipo: g.tipo,
    descripcion: g.descripcion,
    referencia_tipo: g.referencia_tipo || 'general',
    referencia_id: g.referencia_id || null,
    fecha_emision: formatDate(g.fecha_emision),
    fecha_vencimiento: formatDate(g.fecha_vencimiento),
    importe_total: g.importe_total,
  }
  formError.value = ''
  showForm.value = true
}

function openPago(g) {
  pagoTarget.value = g
  pagoForm.value = { importe: +(g.importe_total) - +(g.pagado), fecha_pago: today(), notas: '' }
  pagoError.value = ''
  showPagoModal.value = true
}

function openImagenes(g) {
  imagenTarget.value = g
  filesToUpload.value = []
  showImagenesModal.value = true
}

function confirmDelete(g) {
  toDelete.value = g
  showDelete.value = true
}

async function saveGasto() {
  formError.value = ''
  saving.value = true
  try {
    const fd = new FormData()
    Object.entries(form.value).forEach(([k, v]) => {
      if (v !== null && v !== undefined && v !== '') fd.append(k, v)
    })
    if (editTarget.value) {
      fd.append('_method', 'PUT')
      await store.updateGasto(editTarget.value.id, fd)
    } else {
      await store.createGasto(fd)
    }
    showForm.value = false
    await loadGastos(currentPage.value)
  } catch (e) {
    formError.value = e.displayMessage || 'Error al guardar el gasto'
  } finally {
    saving.value = false
  }
}

async function registrarPagoGasto() {
  pagoError.value = ''
  pagando.value = true
  try {
    const updatedGasto = await store.registrarPagoGasto(pagoTarget.value.id, pagoForm.value)
    // Si el modal de edición está abierto para este gasto, actualizarlo
    if (editTarget.value && editTarget.value.id === updatedGasto.id) {
      editTarget.value = updatedGasto
    }
    // Si el gasto queda completamente pagado, generar recibo automáticamente
    if (updatedGasto.estado === 'pagado' && updatedGasto.detalles?.length) {
      const ultimoDetalle = updatedGasto.detalles[updatedGasto.detalles.length - 1]
      generarReciboGasto(updatedGasto, ultimoDetalle)
    }
    showPagoModal.value = false
    await loadGastos(currentPage.value)
  } catch (e) {
    pagoError.value = e.displayMessage || 'Error al registrar el pago'
  } finally {
    pagando.value = false
  }
}

function onFilesChange(e) {
  filesToUpload.value = Array.from(e.target.files)
}

async function subirImagenes() {
  uploading.value = true
  try {
    const fd = new FormData()
    filesToUpload.value.forEach((f) => fd.append('imagenes[]', f))
    const updated = await store.subirImagenes(imagenTarget.value.id, fd)
    imagenTarget.value = updated
    filesToUpload.value = []
    await loadGastos(currentPage.value)
  } catch (e) {
    alert(e.displayMessage || 'Error al subir imágenes')
  } finally {
    uploading.value = false
  }
}

async function eliminarImg(imgId) {
  if (!confirm('¿Eliminar esta imagen?')) return
  try {
    const updated = await store.eliminarImagen(imagenTarget.value.id, imgId)
    imagenTarget.value = updated
    await loadGastos(currentPage.value)
  } catch (e) {
    alert(e.displayMessage || 'Error al eliminar')
  }
}

async function doDelete() {
  saving.value = true
  try {
    await store.deleteGasto(toDelete.value.id)
    showDelete.value = false
    await loadGastos(currentPage.value)
  } catch (e) {
    alert(e.displayMessage || 'Error al eliminar')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadGastos(1)
  trasterosStore.fetchTrasteros()
  pisosStore.fetchPisos()
})
</script>

<style scoped>
.imagenes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.imagen-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  text-align: center;
}
.imagen-item img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid var(--gris-claro);
}
.imagen-name {
  font-size: 0.78rem;
  color: var(--gris);
  word-break: break-word;
}
.pagos-realizados {
  margin-top: 1.5rem;
  border-top: 1px solid var(--gris-claro);
  padding-top: 1rem;
}
.pagos-realizados-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--gris);
  margin-bottom: 0.75rem;
}
</style>
