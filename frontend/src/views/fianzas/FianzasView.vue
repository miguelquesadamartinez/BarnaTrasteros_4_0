<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">{{ soloDevueltas ? '↩️ Fianzas Devueltas' : '💰 Fianzas Activas' }}</h1>
      <button v-if="!soloDevueltas" class="btn btn-primary" @click="openNew">+ Nueva Fianza</button>
    </div>

    <div class="card">
      <div class="card-header">
        <input
          v-model="search"
          class="form-control"
          style="max-width: 320px"
          placeholder="Buscar por cliente..."
        />
        <span class="text-muted">{{ store.pagination.total }} fianzas</span>
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
              <th>Cliente</th>
              <th>Trastero / Piso</th>
              <th>Importe</th>
              <th>Fecha entrega</th>
              <th v-if="soloDevueltas">Fecha devolución</th>
              <th>Notas</th>
              <th v-if="!soloDevueltas">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.fianzas.length === 0">
              <td colspan="6" class="text-center text-muted" style="padding:2rem">Sin resultados</td>
            </tr>
            <tr v-for="f in store.fianzas" :key="f.id">
              <td><strong>{{ f.cliente?.nombre }} {{ f.cliente?.apellido }}</strong></td>
              <td>
                <span v-if="f.numero" class="badge badge-info">{{ f.tipo === 'piso' ? '🏠' : '📦' }} {{ f.numero }}</span>
                <span v-else class="text-muted">—</span>
              </td>
              <td>{{ Number(f.importe).toFixed(2) }} €</td>
              <td>{{ formatFecha(f.fecha_entrega) }}</td>
              <td v-if="soloDevueltas">{{ formatFecha(f.fecha_devolucion) }}</td>
              <td>
                <span v-if="!f.notas">—</span>
                <span
                  v-else-if="f.notas.length > 40"
                  style="cursor:pointer;text-decoration:underline dotted"
                  title="Ver nota completa"
                  @click="verNota(f.notas)"
                >{{ f.notas.slice(0, 40) }}...</span>
                <span v-else>{{ f.notas }}</span>
              </td>
              <td v-if="!soloDevueltas">
                <div class="actions-cell">
                  <button class="btn btn-warning btn-sm" title="Editar fianza" @click="openEdit(f)">✏️ Editar</button>
                  <button class="btn btn-danger btn-sm" title="Marcar como devuelta" @click="confirmDevolver(f)">↩️ Devolver</button>
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
    <AppModal v-model="showModal" :title="editing ? 'Editar Fianza' : 'Nueva Fianza'" size="md">
      <form @submit.prevent="save">
        <div class="alert alert-danger" v-if="formError">{{ formError }}</div>

        <div class="form-group">
          <label class="form-label">Cliente *</label>
          <SearchSelect
            :model-value="form.cliente_id"
            @update:model-value="onClienteSelect"
            :options="clienteOptions"
            placeholder="Buscar cliente..."
            :allow-clear="false"
          />
        </div>

        <div v-if="clienteSinUnidad" class="alert alert-danger">
          Este cliente no tiene ningún trastero o piso asociado. Debe asignarle uno antes de poder añadir una fianza.
        </div>

        <div v-else class="form-group">
          <label class="form-label">Trastero / Piso relacionado *</label>
          <SearchSelect
            v-model="unidadSeleccionada"
            :options="unidadOptions"
            placeholder="Buscar trastero o piso..."
            :allow-clear="false"
          />
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Importe (€) *</label>
            <input v-model.number="form.importe" class="form-control" type="number" step="0.01" min="0" required />
          </div>
          <div class="form-group">
            <label class="form-label">Fecha de entrega *</label>
            <input v-model="form.fecha_entrega" class="form-control" type="date" required />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Notas</label>
          <textarea v-model="form.notas" class="form-control" rows="2"></textarea>
        </div>

        <div v-if="editing" class="form-group">
          <label class="form-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
            <input type="checkbox" v-model="form.devuelta" style="width:16px;height:16px" />
            <span>Fianza devuelta</span>
          </label>
          <div v-if="form.devuelta" style="margin-top:.5rem">
            <label class="form-label">Fecha de devolución</label>
            <input v-model="form.fecha_devolucion" class="form-control" type="date" />
          </div>
          <small class="text-muted">Al marcarla como devuelta, desaparecerá del listado.</small>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button type="submit" class="btn btn-primary" :disabled="saving || clienteSinUnidad || !unidadSeleccionada">
            {{ saving ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear') }}
          </button>
        </div>
      </form>
    </AppModal>

    <!-- Modal Confirm Devolver -->
    <AppModal v-model="showDevolver" title="Confirmar devolución" size="sm">
      <p>¿Confirmas la devolución de la fianza de <strong>{{ toDevolver?.cliente?.nombre }} {{ toDevolver?.cliente?.apellido }}</strong>? Pasará al listado de fianzas devueltas.</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showDevolver = false">Cancelar</button>
        <button class="btn btn-danger" @click="doDevolver" :disabled="saving">Devolver</button>
      </div>
    </AppModal>

    <!-- Modal Ver Nota -->
    <AppModal v-model="showNota" title="Nota" size="sm">
      <p style="white-space:pre-wrap">{{ notaTexto }}</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showNota = false">Cerrar</button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useFianzasStore } from '@/stores/fianzas'
import { useClientesStore } from '@/stores/clientes'
import { useTrasterosStore } from '@/stores/trasteros'
import { usePisosStore } from '@/stores/pisos'
import AppModal from '@/components/AppModal.vue'
import AppPagination from '@/components/AppPagination.vue'
import SearchSelect from '@/components/SearchSelect.vue'
import { DEFAULT_PER_PAGE, PER_PAGE_OPTIONS } from '@/config/pagination'
import { usePdfRecibo } from '@/composables/usePdfRecibo'

const { generarComprobanteFianza } = usePdfRecibo()

const props = defineProps({
  soloDevueltas: { type: Boolean, default: false },
})

const store = useFianzasStore()
const route = useRoute()
const clientesStore = useClientesStore()
const trasterosStore = useTrasterosStore()
const pisosStore = usePisosStore()

const search = ref('')
const currentPage = ref(1)
const perPage = ref(DEFAULT_PER_PAGE)
const showModal = ref(false)
const showDevolver = ref(false)
const editing = ref(false)
const saving = ref(false)
const formError = ref('')
const toDevolver = ref(null)
const originalDevuelta = ref(false)
const showNota = ref(false)
const notaTexto = ref('')

function verNota(texto) {
  notaTexto.value = texto
  showNota.value = true
}

function formatFecha(f) {
  if (!f) return '—'
  return new Date(f).toLocaleDateString('es-ES')
}

function todayStr() {
  const hoy = new Date()
  return `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`
}

const emptyForm = () => ({
  cliente_id: null,
  tipo: null,
  referencia_id: null,
  numero: null,
  importe: null,
  fecha_entrega: todayStr(),
  notas: '',
  devuelta: false,
  fecha_devolucion: '',
})
const form = ref(emptyForm())

const clienteOptions = computed(() =>
  clientesStore.clientes.map((c) => ({ value: c.id, label: `${c.nombre} ${c.apellido} — ${c.dni}` }))
)

const unidadSeleccionada = ref(null)
const unidadOptions = computed(() => {
  if (!form.value.cliente_id) return []
  return [
    ...trasterosStore.trasteros
      .filter((t) => t.cliente_id === form.value.cliente_id)
      .map((t) => ({ value: `trastero:${t.id}:${t.numero}`, label: `📦 ${t.numero} — ${t.tamanyo} (${t.piso})` })),
    ...pisosStore.pisos
      .filter((p) => p.cliente_id === form.value.cliente_id)
      .map((p) => ({ value: `piso:${p.id}:${p.numero}`, label: `🏠 ${p.numero} — ${p.piso}` })),
  ]
})

const clienteSinUnidad = computed(() => !!form.value.cliente_id && unidadOptions.value.length === 0)

watch(unidadSeleccionada, (val) => {
  if (!val) {
    form.value.tipo = null
    form.value.referencia_id = null
    form.value.numero = null
    return
  }
  const [tipo, id, numero] = val.split(':')
  form.value.tipo = tipo
  form.value.referencia_id = Number(id)
  form.value.numero = numero
})

function onClienteSelect(val) {
  form.value.cliente_id = val
  unidadSeleccionada.value = unidadOptions.value.length === 1 ? unidadOptions.value[0].value : null
}

function estadoParam() {
  return props.soloDevueltas ? 'devueltas' : 'activas'
}

function fetchList(page = currentPage.value) {
  return store.fetchFianzas({ search: search.value, estado: estadoParam(), page, per_page: perPage.value })
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    fetchList(1)
  }, 350)
})

watch(() => props.soloDevueltas, () => {
  search.value = ''
  currentPage.value = 1
  fetchList(1)
})

function onPageChange(page) {
  currentPage.value = page
  fetchList(page)
}

function onPerPageChange() {
  currentPage.value = 1
  fetchList(1)
}

function openNew() {
  editing.value = false
  form.value = emptyForm()
  unidadSeleccionada.value = null
  formError.value = ''
  showModal.value = true
}

function openEdit(f) {
  editing.value = true
  originalDevuelta.value = !!f.devuelta
  form.value = {
    cliente_id: f.cliente_id,
    tipo: f.tipo,
    referencia_id: f.referencia_id,
    numero: f.numero,
    importe: Number(f.importe),
    fecha_entrega: f.fecha_entrega,
    notas: f.notas ?? '',
    devuelta: !!f.devuelta,
    fecha_devolucion: f.fecha_devolucion ?? '',
    _id: f.id,
  }
  unidadSeleccionada.value = f.tipo ? `${f.tipo}:${f.referencia_id}:${f.numero}` : null
  formError.value = ''
  showModal.value = true
}

function confirmDevolver(f) {
  toDevolver.value = f
  showDevolver.value = true
}

async function save() {
  formError.value = ''
  saving.value = true
  try {
    const payload = {
      cliente_id: form.value.cliente_id,
      tipo: form.value.tipo,
      referencia_id: form.value.referencia_id,
      numero: form.value.numero,
      importe: form.value.importe,
      fecha_entrega: form.value.fecha_entrega,
      notas: form.value.notas || '',
      devuelta: form.value.devuelta,
      fecha_devolucion: form.value.fecha_devolucion || null,
    }

    if (editing.value) {
      const updated = await store.updateFianza(form.value._id, payload)
      if (payload.devuelta && !originalDevuelta.value) {
        generarComprobanteFianza(updated)
      }
    } else {
      await store.createFianza(payload)
    }

    showModal.value = false
    await fetchList()
  } catch (e) {
    formError.value = e.displayMessage || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

async function doDevolver() {
  saving.value = true
  try {
    const f = toDevolver.value
    const updated = await store.updateFianza(f.id, {
      cliente_id: f.cliente_id,
      tipo: f.tipo,
      referencia_id: f.referencia_id,
      numero: f.numero,
      importe: f.importe,
      fecha_entrega: f.fecha_entrega,
      notas: f.notas,
      devuelta: true,
    })
    showDevolver.value = false
    generarComprobanteFianza(updated)
    await fetchList()
  } catch (e) {
    alert(e.displayMessage || 'Error al marcar como devuelta')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (route.query.q) search.value = String(route.query.q)
  fetchList(1)
  clientesStore.fetchAllClientes()
  trasterosStore.fetchTrasteros()
  pisosStore.fetchPisos()
})
</script>
