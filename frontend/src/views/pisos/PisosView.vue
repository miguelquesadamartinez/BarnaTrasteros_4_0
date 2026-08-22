<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">🏠 Gestión de Pisos</h1>
      <button class="btn btn-primary" @click="openNew">+ Nuevo Piso</button>
    </div>

    <div class="card">
      <div class="card-header">
        <input
          v-model="search"
          class="form-control"
          style="max-width: 320px"
          placeholder="Buscar por número o piso..."
        />
        <span class="text-muted">{{ store.pisos.length }} pisos</span>
      </div>

      <div v-if="store.loading" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="store.error" class="alert alert-danger">{{ store.error }}</div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Número</th>
              <th>Piso</th>
              <th>Precio/mes</th>
              <th>Cliente</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="6" class="text-center text-muted" style="padding:2rem">Sin resultados</td>
            </tr>
            <tr v-for="p in filtered" :key="p.id">
              <td><strong>{{ p.numero }}</strong></td>
              <td>{{ p.piso }}</td>
              <td>{{ formatMoney(p.precio_mensual) }}</td>
              <td>
                <span v-if="p.cliente">{{ p.cliente.nombre }} {{ p.cliente.apellido }}</span>
                <span v-else class="text-muted">—</span>
              </td>
              <td>
                <span class="badge" :class="p.cliente_id ? 'badge-success' : 'badge-muted'">
                  {{ p.cliente_id ? 'Alquilado' : 'Libre' }}
                </span>
              </td>
              <td>
                <div class="actions-cell">
                  <button class="btn btn-warning btn-sm" title="Editar piso" @click="openEdit(p)">✏️ Editar</button>
                  <button v-if="p.cliente_id" class="btn btn-secondary btn-sm" title="Dar de baja al cliente" @click="openBaja(p)">🚪 Dar de baja</button>
                  <button class="btn btn-danger btn-sm" title="Eliminar piso" @click="confirmDelete(p)">🗑️ Eliminar</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <AppModal v-model="showModal" :title="editing ? 'Editar Piso' : 'Nuevo Piso'" size="md">
      <form @submit.prevent="save">
        <div class="alert alert-danger" v-if="formError">{{ formError }}</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Número *</label>
            <input v-model="form.numero" class="form-control" required placeholder="Ej: P-1A" />
          </div>
          <div class="form-group">
            <label class="form-label">Piso *</label>
            <input v-model="form.piso" class="form-control" required placeholder="Ej: 1º" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Precio mensual (€) *</label>
          <input v-model.number="form.precio_mensual" class="form-control" type="number" step="0.01" min="0" required />
        </div>
        <div class="form-group">
          <label class="form-label">Cliente asignado</label>
          <SearchSelect
            v-model="form.cliente_id"
            :options="clienteOptions"
            placeholder="Buscar cliente..."
            :allow-clear="true"
            :disabled="editing && originalHasCliente"
          />
          <small class="text-muted" v-if="editing && originalHasCliente">Usa "Dar de baja" en el listado para liberar esta unidad.</small>
        </div>
        <div class="form-group" v-if="form.cliente_id">
          <label class="form-label">Fecha inicio alquiler</label>
          <input v-model="form.fecha_inicio_alquiler" class="form-control" type="date" />
        </div>
        <div class="form-group">
          <label class="form-label">Notas</label>
          <textarea v-model="form.notas" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear') }}
          </button>
        </div>
      </form>
    </AppModal>

    <AppModal v-model="showDelete" title="Confirmar eliminación" size="sm">
      <p>¿Seguro que deseas eliminar el piso <strong>{{ toDelete?.numero }}</strong>?</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showDelete = false">Cancelar</button>
        <button class="btn btn-danger" @click="doDelete" :disabled="saving">Eliminar</button>
      </div>
    </AppModal>

    <!-- Modal Dar de baja -->
    <AppModal v-model="showBaja" title="Dar de baja" size="sm">
      <div class="alert alert-danger" v-if="bajaError">{{ bajaError }}</div>
      <div v-if="!bajaInfo">
        <p>¿Dar de baja a <strong>{{ bajaTarget?.cliente?.nombre }} {{ bajaTarget?.cliente?.apellido }}</strong> del piso <strong>{{ bajaTarget?.numero }}</strong>? La unidad quedará libre.</p>
      </div>
      <div v-else>
        <p>Antes de dar de baja, revisa lo pendiente para este piso:</p>
        <ul v-if="bajaInfo.pagos_pendientes?.length">
          <li v-for="pago in bajaInfo.pagos_pendientes" :key="'p'+pago.id">
            Pago {{ pago.mes }}/{{ pago.anyo }}: pendiente {{ formatMoney(pago.importe_total - pago.pagado) }} ({{ pago.estado }})
          </li>
        </ul>
        <ul v-if="bajaInfo.fianzas_pendientes?.length">
          <li v-for="f in bajaInfo.fianzas_pendientes" :key="'f'+f.id">
            Fianza sin devolver: {{ formatMoney(f.importe) }}
          </li>
        </ul>
        <p class="text-muted">Puedes dar de baja igualmente; estos importes seguirán pendientes en el sistema.</p>
      </div>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showBaja = false">Cancelar</button>
        <button class="btn btn-danger" @click="doBaja" :disabled="bajaSaving">
          {{ bajaInfo ? 'Dar de baja de todas formas' : 'Dar de baja' }}
        </button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { usePisosStore } from '@/stores/pisos'
import { useClientesStore } from '@/stores/clientes'
import AppModal from '@/components/AppModal.vue'
import SearchSelect from '@/components/SearchSelect.vue'

const route = useRoute()
const store = usePisosStore()
const clientesStore = useClientesStore()

const search = ref('')
const showModal = ref(false)
const showDelete = ref(false)
const editing = ref(false)
const saving = ref(false)
const formError = ref('')
const toDelete = ref(null)
const originalHasCliente = ref(false)

const showBaja = ref(false)
const bajaSaving = ref(false)
const bajaError = ref('')
const bajaTarget = ref(null)
const bajaInfo = ref(null)

function formatDate(v) { return v ? v.split('T')[0] : '' }

const emptyForm = () => ({
  numero: '', piso: '', precio_mensual: 0,
  cliente_id: null, fecha_inicio_alquiler: '', notas: '',
})
const form = ref(emptyForm())

const clienteOptions = computed(() =>
  clientesStore.clientes.map((c) => ({
    value: c.id,
    label: `${c.nombre} ${c.apellido} — ${c.dni}`,
  }))
)

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return store.pisos.filter(
    (p) => p.numero.toLowerCase().includes(q) || p.piso.toLowerCase().includes(q)
  )
})

function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}

function openNew() {
  editing.value = false
  form.value = emptyForm()
  formError.value = ''
  showModal.value = true
}

function openEdit(p) {
  editing.value = true
  originalHasCliente.value = !!p.cliente_id
  form.value = {
    numero: p.numero, piso: p.piso, precio_mensual: p.precio_mensual,
    cliente_id: p.cliente_id ?? null,
    fecha_inicio_alquiler: formatDate(p.fecha_inicio_alquiler) ?? '',
    notas: p.notas ?? '', _id: p.id,
  }
  formError.value = ''
  showModal.value = true
}

function confirmDelete(p) {
  toDelete.value = p
  showDelete.value = true
}

function openBaja(p) {
  bajaTarget.value = p
  bajaInfo.value = null
  bajaError.value = ''
  showBaja.value = true
}

async function doBaja() {
  bajaSaving.value = true
  bajaError.value = ''
  try {
    const result = await store.darBajaPiso(bajaTarget.value.id, !!bajaInfo.value)
    if (result.ok) {
      showBaja.value = false
    } else {
      bajaInfo.value = result.pendientes
    }
  } catch (e) {
    bajaError.value = e.displayMessage || 'Error al dar de baja'
  } finally {
    bajaSaving.value = false
  }
}

async function save() {
  formError.value = ''
  saving.value = true
  try {
    if (editing.value) {
      await store.updatePiso(form.value._id, { ...form.value })
    } else {
      await store.createPiso({ ...form.value })
    }
    showModal.value = false
  } catch (e) {
    formError.value = e.displayMessage || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

async function doDelete() {
  saving.value = true
  try {
    await store.deletePiso(toDelete.value.id)
    showDelete.value = false
  } catch (e) {
    alert(e.displayMessage || 'Error al eliminar')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (route.query.q) search.value = String(route.query.q)
  store.fetchPisos()
  clientesStore.fetchAllClientes()
})
</script>
