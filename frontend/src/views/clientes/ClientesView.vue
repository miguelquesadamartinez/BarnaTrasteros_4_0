<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">👥 Gestión de Clientes</h1>
      <button class="btn btn-primary" @click="openNew">+ Nuevo Cliente</button>
    </div>

    <div class="card">
      <div class="card-header">
        <input
          v-model="search"
          class="form-control"
          style="max-width: 320px"
          placeholder="Buscar por nombre, apellido o DNI..."
        />
        <span class="text-muted">{{ store.pagination.total }} clientes</span>
        <div class="filter-item" style="margin-left:auto">
          <span class="filter-label">Por página</span>
          <select v-model="perPage" class="form-control" style="max-width:90px" @change="onPerPageChange">
            <option v-for="n in PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
          </select>
        </div>
      </div>

      <div v-if="store.loading" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="store.error" class="alert alert-danger">{{ store.error }}</div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>DNI</th>
              <th>Teléfono</th>
              <th>Trasteros</th>
              <th>Pisos</th>
              <th>DNI Foto</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.clientes.length === 0">
              <td colspan="7" class="text-center text-muted" style="padding:2rem">Sin resultados</td>
            </tr>
            <tr v-for="c in store.clientes" :key="c.id">
              <td><strong>{{ c.nombre }} {{ c.apellido }}</strong></td>
              <td>{{ c.dni }}</td>
              <td>{{ c.telefono || '—' }}</td>
              <td>
                <span v-if="c.trasteros?.length">
                  <span v-for="t in c.trasteros" :key="t.id" class="badge badge-info" style="margin-right:.25rem">{{ t.numero }}</span>
                </span>
                <span v-else class="text-muted">—</span>
              </td>
              <td>
                <span v-if="c.pisos?.length">
                  <span v-for="p in c.pisos" :key="p.id" class="badge badge-primary" style="margin-right:.25rem">{{ p.numero }}</span>
                </span>
                <span v-else class="text-muted">—</span>
              </td>
              <td>
                <a v-if="c.foto_dni" :href="fotoUrl(c.foto_dni)" target="_blank" class="btn btn-info btn-sm">Ver foto</a>
                <span v-else class="text-muted">—</span>
              </td>
              <td>
                <div class="actions-cell">
                  <button class="btn btn-warning btn-sm" title="Editar cliente" @click="openEdit(c)">✏️ Editar</button>
                  <button class="btn btn-danger btn-sm" title="Eliminar cliente" @click="confirmDelete(c)">🗑️ Eliminar</button>
                </div>
              </td>
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

    <!-- Modal Formulario -->
    <AppModal v-model="showModal" :title="editing ? 'Editar Cliente' : 'Nuevo Cliente'" size="lg">
      <form @submit.prevent="save" enctype="multipart/form-data">
        <div class="alert alert-danger" v-if="formError">{{ formError }}</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nombre *</label>
            <input v-model="form.nombre" class="form-control" required />
          </div>
          <div class="form-group">
            <label class="form-label">Apellido *</label>
            <input v-model="form.apellido" class="form-control" required />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">DNI *</label>
            <input v-model="form.dni" class="form-control" required placeholder="12345678A" />
          </div>
          <div class="form-group">
            <label class="form-label">Teléfono</label>
            <input v-model="form.telefono" class="form-control" placeholder="6XXXXXXXX" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input v-model="form.email" class="form-control" type="email" placeholder="cliente@email.com" />
        </div>
        <div class="form-group">
          <label class="form-label">Dirección</label>
          <input v-model="form.direccion" class="form-control" placeholder="Carrer de..., 123" />
        </div>
        <div class="form-row">
          <div class="form-group" style="flex:0 0 140px">
            <label class="form-label">Código Postal</label>
            <input v-model="form.codigo_postal" class="form-control" placeholder="08001" maxlength="10" />
          </div>
          <div class="form-group">
            <label class="form-label">Ciudad</label>
            <input v-model="form.ciudad" class="form-control" placeholder="Barcelona" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
            <input type="checkbox" v-model="form.necesita_factura" style="width:16px;height:16px" />
            <span>Necesita factura mensual</span>
            <small class="text-muted">(se incluirá en la generación automática de facturas)</small>
          </label>
        </div>
        <div class="form-group">
          <label class="form-label">Foto del DNI</label>
          <input type="file" class="form-control" accept="image/*,.pdf" @change="onFotoChange" />
          <small class="text-muted">JPG, PNG o PDF. Máx 5MB.</small>
          <div v-if="form.foto_dni_preview || (editing && currentFoto)" class="mt-1">
            <img v-if="form.foto_dni_preview" :src="form.foto_dni_preview" style="max-height:100px;border-radius:4px;margin-top:.5rem" />
            <small v-if="editing && currentFoto && !form.foto_dni_preview" class="text-muted">Foto actual guardada</small>
          </div>
        </div>

        <div v-if="editing" class="form-group">
          <label class="form-label">Contrato de alquiler</label><br />
          <a v-if="currentContrato" :href="contratoUrl(currentContrato)" target="_blank" class="btn btn-info btn-sm">📄 Ver contrato</a>
          <button
            v-else
            type="button"
            class="btn btn-secondary btn-sm"
            :disabled="generandoContrato"
            @click="generarContratoManual"
          >
            {{ generandoContrato ? 'Generando...' : '📄 Generar contrato' }}
          </button>
        </div>

        <div v-if="editing && pendienteClienteEdit > 0" class="form-group">
          <label class="form-label">Pago pendiente: <span class="text-danger">{{ formatMoney(pendienteClienteEdit) }}</span></label><br />
          <button
            type="button"
            class="btn btn-success btn-sm"
            :disabled="avisandoImpago"
            @click="avisarImpagoCliente"
          >
            {{ avisandoImpago ? 'Enviando...' : '🔔 Avisar de pago pendiente' }}
          </button>
        </div>

        <hr style="margin: 1rem 0; border-color: var(--gris-borde)" />
        <p style="font-size:.85rem;color:var(--gris-texto);margin-bottom:.75rem">
          <strong>Propiedades asociadas</strong> — Opcional, se puede completar más tarde.
        </p>

        <div class="form-group">
          <label class="form-label">Trasteros asignados</label>
          <div v-if="form.trastero_ids.length" style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.5rem">
            <span
              v-for="tid in form.trastero_ids"
              :key="tid"
              class="badge badge-info"
              style="display:inline-flex;align-items:center;gap:.35rem;font-size:.85rem;padding:.25rem .55rem"
            >
              {{ trasteroLabel(tid) }}
              <button
                type="button"
                @click="removeTrastero(tid)"
                style="background:none;border:none;cursor:pointer;padding:0 2px;font-size:1.1rem;line-height:1;color:inherit;opacity:.8"
              >&times;</button>
            </span>
          </div>
          <span v-else class="text-muted" style="font-size:.85rem;display:block;margin-bottom:.4rem">Ningún trastero asignado</span>
          <SearchSelect
            v-model="addTrasteroId"
            :options="trasteroAddOptions"
            placeholder="Añadir trastero..."
            :allow-clear="false"
          />
          <small class="text-muted">Al guardar se actualizan las asignaciones automáticamente.</small>
        </div>

        <div class="form-group">
          <label class="form-label">Piso asignado</label>
          <SearchSelect
            v-model="form.piso_id"
            :options="pisoOptions"
            placeholder="Buscar piso..."
            :allow-clear="true"
          />
        </div>

        <div class="form-group">
          <label class="form-label">Fianzas</label>
          <div v-if="fianzasMostradas.length" style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.5rem">
            <span
              v-for="f in fianzasMostradas"
              :key="f._key"
              class="badge badge-info"
              style="display:inline-flex;align-items:center;gap:.35rem;font-size:.85rem;padding:.25rem .55rem"
            >
              {{ Number(f.importe).toFixed(2) }} € — {{ formatFecha(f.fecha_entrega) }}{{ f.numero ? ` (${f.numero})` : '' }}
              <button
                type="button"
                @click="quitarFianza(f)"
                :title="editing ? 'Marcar como devuelta' : 'Quitar'"
                style="background:none;border:none;cursor:pointer;padding:0 2px;font-size:1.1rem;line-height:1;color:inherit;opacity:.8"
              >&times;</button>
            </span>
          </div>
          <span v-else class="text-muted" style="font-size:.85rem;display:block;margin-bottom:.4rem">Ninguna fianza registrada</span>

          <div v-if="unidadesClienteOptions.length === 0" class="alert alert-danger" style="font-size:.85rem;padding:.5rem .75rem">
            Asigna primero un trastero o piso a este cliente para poder añadir una fianza.
          </div>
          <template v-else>
            <div class="form-group" v-if="unidadesClienteOptions.length > 1">
              <SearchSelect
                v-model="unidadFianzaSeleccionada"
                :options="unidadesClienteOptions"
                placeholder="Trastero o piso para esta fianza..."
                :allow-clear="false"
              />
            </div>
            <div class="form-row">
              <div class="form-group" style="flex:0 0 130px">
                <input v-model.number="nuevaFianza.importe" class="form-control" type="number" step="0.01" min="0" placeholder="Importe €" />
              </div>
              <div class="form-group" style="flex:0 0 160px">
                <input v-model="nuevaFianza.fecha_entrega" class="form-control" type="date" />
              </div>
              <div class="form-group" style="flex:0 0 auto">
                <button type="button" class="btn btn-secondary btn-sm" @click="addFianza" :disabled="addingFianza || !unidadFianzaSeleccionada">+ Añadir fianza</button>
              </div>
            </div>
          </template>
          <small class="text-muted">
            {{ editing ? 'Al quitar una fianza se marca como devuelta y desaparece del listado de fianzas.' : 'Se crearán al guardar el cliente.' }}
          </small>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear') }}
          </button>
        </div>
      </form>
    </AppModal>

    <!-- Modal Confirm Delete -->
    <AppModal v-model="showDelete" title="Confirmar eliminación" size="sm">
      <p>¿Seguro que deseas eliminar al cliente <strong>{{ toDelete?.nombre }} {{ toDelete?.apellido }}</strong>?</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showDelete = false">Cancelar</button>
        <button class="btn btn-danger" @click="doDelete" :disabled="saving">Eliminar</button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useClientesStore } from '@/stores/clientes'
import { useTrasterosStore } from '@/stores/trasteros'
import { usePisosStore } from '@/stores/pisos'
import { useFianzasStore } from '@/stores/fianzas'
import AppModal from '@/components/AppModal.vue'
import AppPagination from '@/components/AppPagination.vue'
import SearchSelect from '@/components/SearchSelect.vue'
import api from '@/api'
import { DEFAULT_PER_PAGE, PER_PAGE_OPTIONS } from '@/config/pagination'

const route = useRoute()
const toast = useToast()
const store = useClientesStore()
const trasterosStore = useTrasterosStore()
const pisosStore = usePisosStore()
const fianzasStore = useFianzasStore()

const search = ref('')
const currentPage = ref(1)
const perPage = ref(DEFAULT_PER_PAGE)
const showModal = ref(false)
const showDelete = ref(false)
const editing = ref(false)
const saving = ref(false)
const formError = ref('')
const toDelete = ref(null)
const currentFoto = ref(null)
const currentContrato = ref(null)
const generandoContrato = ref(false)
const pendienteClienteEdit = ref(0)
const avisandoImpago = ref(false)

const apiBase = import.meta.env.VITE_API_BASE_URL
  ? import.meta.env.VITE_API_BASE_URL.replace('/api', '')
  : ''

function fotoUrl(ruta) {
  return `${apiBase}/storage/${ruta}`
}

function contratoUrl(ruta) {
  return `${apiBase}/storage/${ruta}`
}

function formatFecha(f) {
  if (!f) return '—'
  return new Date(f).toLocaleDateString('es-ES')
}

function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}

function todayStr() {
  const hoy = new Date()
  return `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`
}

const emptyForm = () => ({
  nombre: '', apellido: '', dni: '', telefono: '',
  email: '',
  direccion: '', codigo_postal: '', ciudad: '', necesita_factura: false,
  foto_dni_file: null, foto_dni_preview: null,
  trastero_ids: [], piso_id: null,
})
const form = ref(emptyForm())

// Fianzas del cliente en edición (persistidas) y fianzas pendientes al crear (aún no hay cliente_id)
const fianzasCliente = ref([])
const pendingFianzas = ref([])
const nuevaFianza = ref({ importe: null, fecha_entrega: todayStr() })
const addingFianza = ref(false)

const fianzasMostradas = computed(() =>
  editing.value
    ? fianzasCliente.value.map((f) => ({ ...f, _key: f.id }))
    : pendingFianzas.value.map((f) => ({ ...f, _key: f._tempId }))
)

// Trasteros/piso actualmente seleccionados en el formulario, para poder vincular la fianza a uno de ellos
const unidadesClienteOptions = computed(() => {
  const opciones = form.value.trastero_ids
    .map((id) => trasterosStore.trasteros.find((t) => t.id === id))
    .filter(Boolean)
    .map((t) => ({ value: `trastero:${t.id}:${t.numero}`, label: `📦 ${t.numero} — ${t.tamanyo} (${t.piso})` }))
  if (form.value.piso_id) {
    const p = pisosStore.pisos.find((pp) => pp.id === form.value.piso_id)
    if (p) opciones.push({ value: `piso:${p.id}:${p.numero}`, label: `🏠 ${p.numero} — ${p.piso}` })
  }
  return opciones
})

const unidadFianzaSeleccionada = ref(null)
watch(unidadesClienteOptions, (opts) => {
  if (opts.length === 1) {
    unidadFianzaSeleccionada.value = opts[0].value
  } else if (!opts.find((o) => o.value === unidadFianzaSeleccionada.value)) {
    unidadFianzaSeleccionada.value = null
  }
}, { immediate: true })

async function loadFianzasCliente(clienteId) {
  fianzasCliente.value = await fianzasStore.fetchFianzasCliente(clienteId)
}

async function addFianza() {
  if (!nuevaFianza.value.importe || !nuevaFianza.value.fecha_entrega || !unidadFianzaSeleccionada.value) return
  const [tipo, id, numero] = unidadFianzaSeleccionada.value.split(':')
  const unidad = { tipo, referencia_id: Number(id), numero }

  if (!editing.value) {
    pendingFianzas.value.push({
      _tempId: Date.now() + Math.random(),
      importe: nuevaFianza.value.importe,
      fecha_entrega: nuevaFianza.value.fecha_entrega,
      ...unidad,
    })
    nuevaFianza.value = { importe: null, fecha_entrega: todayStr() }
    return
  }
  addingFianza.value = true
  try {
    await fianzasStore.createFianza({
      cliente_id: form.value._id,
      importe: nuevaFianza.value.importe,
      fecha_entrega: nuevaFianza.value.fecha_entrega,
      devuelta: false,
      ...unidad,
    })
    nuevaFianza.value = { importe: null, fecha_entrega: todayStr() }
    await loadFianzasCliente(form.value._id)
  } catch (e) {
    formError.value = e.displayMessage || 'Error al añadir la fianza'
  } finally {
    addingFianza.value = false
  }
}

async function quitarFianza(f) {
  if (!editing.value) {
    pendingFianzas.value = pendingFianzas.value.filter((p) => p._tempId !== f._tempId)
    return
  }
  try {
    await fianzasStore.updateFianza(f.id, {
      cliente_id: f.cliente_id,
      tipo: f.tipo,
      referencia_id: f.referencia_id,
      numero: f.numero,
      importe: f.importe,
      fecha_entrega: f.fecha_entrega,
      notas: f.notas,
      devuelta: true,
    })
    await loadFianzasCliente(form.value._id)
  } catch (e) {
    formError.value = e.displayMessage || 'Error al quitar la fianza'
  }
}

// Trastero añadir: libres o del cliente actual, excluye los ya seleccionados
const addTrasteroId = ref(null)
watch(addTrasteroId, (val) => {
  if (val && !form.value.trastero_ids.includes(val)) {
    form.value.trastero_ids.push(val)
    nextTick(() => { addTrasteroId.value = null })
  }
})

function removeTrastero(id) {
  form.value.trastero_ids = form.value.trastero_ids.filter((i) => i !== id)
}

function trasteroLabel(id) {
  const t = trasterosStore.trasteros.find((tt) => tt.id === id)
  return t ? `${t.numero} — ${t.tamanyo} (${t.piso})` : `#${id}`
}

const trasteroAddOptions = computed(() =>
  trasterosStore.trasteros
    .filter((t) => {
      const isFree = !t.cliente_id
      const isThisClient = editing.value && t.cliente_id === form.value._clienteId
      return (isFree || isThisClient) && !form.value.trastero_ids.includes(t.id)
    })
    .map((t) => ({ value: t.id, label: `${t.numero} — ${t.tamanyo} (${t.piso})` }))
)

const pisoOptions = computed(() =>
  pisosStore.pisos
    .filter((p) => !p.cliente_id || (editing.value && form.value._clienteId && p.cliente_id === form.value._clienteId))
    .map((p) => ({ value: p.id, label: `${p.numero} — ${p.piso}` }))
)

// Debounce de búsqueda: al cambiar el texto, volver a página 1
let searchTimer = null
watch(search, (val) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    store.fetchClientes({ search: val, page: 1, per_page: perPage.value })
  }, 350)
})

function onPageChange(page) {
  currentPage.value = page
  store.fetchClientes({ search: search.value, page, per_page: perPage.value })
}

function onPerPageChange() {
  currentPage.value = 1
  store.fetchClientes({ search: search.value, page: 1, per_page: perPage.value })
}

function onFotoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  form.value.foto_dni_file = file
  if (file.type.startsWith('image/')) {
    const reader = new FileReader()
    reader.onload = (ev) => { form.value.foto_dni_preview = ev.target.result }
    reader.readAsDataURL(file)
  } else {
    form.value.foto_dni_preview = null
  }
}

function openNew() {
  editing.value = false
  form.value = emptyForm()
  currentFoto.value = null
  currentContrato.value = null
  pendienteClienteEdit.value = 0
  fianzasCliente.value = []
  pendingFianzas.value = []
  nuevaFianza.value = { importe: null, fecha_entrega: todayStr() }
  formError.value = ''
  showModal.value = true
}

function openEdit(c) {
  editing.value = true
  const piso = pisosStore.pisos.find((p) => p.cliente_id === c.id)
  form.value = {
    nombre: c.nombre,
    apellido: c.apellido,
    dni: c.dni,
    telefono: c.telefono ?? '',
    email: c.email ?? '',
    direccion: c.direccion ?? '',
    codigo_postal: c.codigo_postal ?? '',
    ciudad: c.ciudad ?? '',
    necesita_factura: !!c.necesita_factura,
    foto_dni_file: null,
    foto_dni_preview: null,
    trastero_ids: c.trasteros?.map((t) => t.id) ?? [],
    piso_id: piso?.id ?? null,
    _id: c.id,
    _clienteId: c.id,
  }
  currentFoto.value = c.foto_dni
  currentContrato.value = c.contrato_path
  pendingFianzas.value = []
  nuevaFianza.value = { importe: null, fecha_entrega: todayStr() }
  formError.value = ''
  showModal.value = true
  loadFianzasCliente(c.id)
  pendienteClienteEdit.value = 0
  api.get(`/clientes/${c.id}/pendiente-total`)
    .then((res) => { pendienteClienteEdit.value = Number(res.data?.pendiente_total ?? 0) })
    .catch(() => {})
}

function confirmDelete(c) {
  toDelete.value = c
  showDelete.value = true
}

async function generarContratoManual() {
  const tab = window.open('', '_blank')
  generandoContrato.value = true
  try {
    const { data } = await api.post(`/clientes/${form.value._id}/contrato`)
    currentContrato.value = data.path
    tab.location = contratoUrl(data.path)
  } catch (e) {
    tab.close()
    alert(e.displayMessage || 'No se pudo generar el contrato. ¿Tiene el cliente algún trastero o piso asignado?')
  } finally {
    generandoContrato.value = false
  }
}

async function avisarImpagoCliente() {
  avisandoImpago.value = true
  try {
    await api.post(`/clientes/${form.value._id}/avisar-impago`)
    toast.success('Aviso de pago pendiente en cola de envío — llegará en breve')
  } catch (e) {
    toast.error(e.displayMessage || 'No se pudo enviar el aviso.')
  } finally {
    avisandoImpago.value = false
  }
}

async function save() {
  // Se calcula ya (antes de cualquier await) si cambia el trastero/piso asignado, para decidir
  // si hay que (re)generar el contrato sin esperar a que termine el guardado.
  const oldTrasteroIdsPre = editing.value
    ? trasterosStore.trasteros.filter((t) => t.cliente_id === form.value._id).map((t) => t.id)
    : []
  const newTrasteroIdsPre = form.value.trastero_ids
  const trasterosChanged = oldTrasteroIdsPre.length !== newTrasteroIdsPre.length ||
    oldTrasteroIdsPre.some((id) => !newTrasteroIdsPre.includes(id)) ||
    newTrasteroIdsPre.some((id) => !oldTrasteroIdsPre.includes(id))
  const prevPisoPre = editing.value ? pisosStore.pisos.find((p) => p.cliente_id === form.value._id) : null
  const pisoChanged = (prevPisoPre?.id ?? null) !== (form.value.piso_id ?? null)
  const unitsChanged = trasterosChanged || pisoChanged
  const isEditingWithContract = editing.value && !!currentContrato.value
  const shouldGenerateContract = !editing.value || (isEditingWithContract && unitsChanged)

  // Se abre ya (en blanco) para conservar el gesto de usuario; se rellena luego con el PDF.
  const contractTab = shouldGenerateContract ? window.open('', '_blank') : null
  formError.value = ''
  saving.value = true
  try {
    const fd = new FormData()
    fd.append('nombre', form.value.nombre)
    fd.append('apellido', form.value.apellido)
    fd.append('dni', form.value.dni)
    fd.append('telefono', form.value.telefono || '')
    fd.append('direccion', form.value.direccion || '')
    fd.append('codigo_postal', form.value.codigo_postal || '')
    fd.append('ciudad', form.value.ciudad || '')
    fd.append('necesita_factura', form.value.necesita_factura ? '1' : '0')
    if (form.value.foto_dni_file) {
      fd.append('foto_dni', form.value.foto_dni_file)
    }

    let cliente
    if (editing.value) {
      cliente = await store.updateCliente(form.value._id, fd)
    } else {
      cliente = await store.createCliente(fd)
    }

    // Actualizar asignaciones de trastero y piso (también aplica al crear, por si se seleccionaron)
    // Trasteros: diff old vs new
    const oldTrasteroIds = trasterosStore.trasteros
      .filter((t) => t.cliente_id === cliente.id)
      .map((t) => t.id)
    const newTrasteroIds = form.value.trastero_ids
    // Desasignar trasteros eliminados
    for (const id of oldTrasteroIds) {
      if (!newTrasteroIds.includes(id)) {
        const t = trasterosStore.trasteros.find((tt) => tt.id === id)
        if (t) await api.put(`/trasteros/${t.id}`, { ...t, cliente_id: null, fecha_inicio_alquiler: null })
      }
    }
    // Asignar nuevos trasteros
    for (const id of newTrasteroIds) {
      if (!oldTrasteroIds.includes(id)) {
        const t = trasterosStore.trasteros.find((tt) => tt.id === id)
        const hoy = new Date()
        const fechaHoy = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`
        if (t) await api.put(`/trasteros/${t.id}`, { ...t, cliente_id: cliente.id, fecha_inicio_alquiler: fechaHoy })
      }
    }
    // Desasignar piso anterior si cambió
    const prevPiso = pisosStore.pisos.find((p) => p.cliente_id === cliente.id)
    if (prevPiso && prevPiso.id !== form.value.piso_id) {
      await api.put(`/pisos/${prevPiso.id}`, { ...prevPiso, cliente_id: null, fecha_inicio_alquiler: null })
    }
    // Asignar nuevo piso
    if (form.value.piso_id) {
      const p = pisosStore.pisos.find((pp) => pp.id === form.value.piso_id)
      const hoy = new Date()
      const fechaHoy = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`
      if (p) await api.put(`/pisos/${p.id}`, { ...p, cliente_id: cliente.id, fecha_inicio_alquiler: fechaHoy })
    }
    // Refrescar trasteros y pisos
    await trasterosStore.fetchTrasteros()
    await pisosStore.fetchPisos()

    // Crear las fianzas pendientes (sólo aplica al crear cliente)
    for (const f of pendingFianzas.value) {
      await fianzasStore.createFianza({
        cliente_id: cliente.id,
        importe: f.importe,
        fecha_entrega: f.fecha_entrega,
        tipo: f.tipo,
        referencia_id: f.referencia_id,
        numero: f.numero,
        devuelta: false,
      })
    }
    pendingFianzas.value = []

    // Generar contrato al crear cliente, o regenerarlo si ya tenía uno y cambió el trastero/piso asignado
    if (contractTab) {
      try {
        const { data } = await api.post(`/clientes/${cliente.id}/contrato`)
        currentContrato.value = data.path
        contractTab.location = contratoUrl(data.path)
      } catch (e) {
        contractTab.close()
      }
    }

    showModal.value = false
    // Refrescar clientes para reflejar relaciones
    await store.fetchClientes({ search: search.value, page: currentPage.value, per_page: perPage.value })
  } catch (e) {
    if (contractTab) contractTab.close()
    formError.value = e.displayMessage || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

async function doDelete() {
  saving.value = true
  try {
    await store.deleteCliente(toDelete.value.id)
    showDelete.value = false
    await store.fetchClientes({ search: search.value, page: currentPage.value, per_page: perPage.value })
  } catch (e) {
    alert(e.displayMessage || 'Error al eliminar')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (route.query.q) search.value = String(route.query.q)
  store.fetchClientes({ search: search.value, page: 1, per_page: perPage.value })
  trasterosStore.fetchTrasteros()
  pisosStore.fetchPisos()
})
</script>
