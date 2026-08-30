<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">📦 Gestión de Trasteros</h1>
      <button class="btn btn-primary" @click="openNew">+ Nuevo Trastero</button>
    </div>

    <div class="card">
      <div class="card-header">
        <input
          v-model="search"
          class="form-control"
          style="max-width: 320px"
          placeholder="Buscar por número, piso o tamaño..."
        />
        <span class="text-muted">{{ store.trasteros.length }} trasteros</span>
      </div>

      <div v-if="store.loading" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="store.error" class="alert alert-danger">{{ store.error }}</div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Número</th>
              <th>Piso</th>
              <th>Tamaño</th>
              <th>Precio/mes</th>
              <th>Cliente</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="7" class="text-center text-muted" style="padding:2rem">Sin resultados</td>
            </tr>
            <tr v-for="t in filtered" :key="t.id">
              <td><strong>{{ t.numero }}</strong></td>
              <td>{{ t.piso }}</td>
              <td>{{ t.tamanyo }}</td>
              <td>{{ formatMoney(t.precio_mensual) }}</td>
              <td>
                <span v-if="t.cliente">{{ t.cliente.nombre }} {{ t.cliente.apellido }}</span>
                <span v-else class="text-muted">—</span>
              </td>
              <td>
                <span class="badge" :class="t.cliente_id ? 'badge-success' : 'badge-muted'">
                  {{ t.cliente_id ? 'Alquilado' : 'Libre' }}
                </span>
              </td>
              <td>
                <div class="actions-cell">
                  <button class="btn btn-warning btn-sm" title="Editar trastero" @click="openEdit(t)">✏️ Editar</button>
                  <button v-if="t.cliente_id" class="btn btn-secondary btn-sm" title="Dar de baja al cliente" @click="openBaja(t)">🚪 Dar de baja</button>
                  <button class="btn btn-danger btn-sm" title="Eliminar trastero" @click="confirmDelete(t)">🗑️ Eliminar</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <AppModal v-model="showModal" :title="editing ? 'Editar Trastero' : 'Nuevo Trastero'" size="md">
      <form @submit.prevent="save">
        <div class="alert alert-danger" v-if="formError">{{ formError }}</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Número *</label>
            <input v-model="form.numero" class="form-control" required placeholder="Ej: T-01" />
          </div>
          <div class="form-group">
            <label class="form-label">Piso *</label>
            <select v-model="form.piso" class="form-control" required>
              <option value="">Selecciona...</option>
              <option value="Planta Baja">Planta Baja</option>
              <option value="Sótano">Sótano</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tamaño *</label>
            <select v-model="form.tamanyo" class="form-control" required>
              <option value="">Selecciona...</option>
              <option v-for="tam in tamanyosStore.tamanyos" :key="tam.id" :value="tam.nombre">{{ tam.nombre }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Precio mensual (€) *</label>
            <input v-model.number="form.precio_mensual" class="form-control" type="number" step="0.01" min="0" required />
          </div>
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
        <div class="form-row" v-if="form.cliente_id">
          <div class="form-group">
            <label class="form-label">Fecha inicio alquiler</label>
            <input v-model="form.fecha_inicio_alquiler" class="form-control" type="date" />
          </div>
          <div class="form-group">
            <label class="form-label">Fecha de vencimiento</label>
            <input v-model="form.fecha_vencimiento" class="form-control" type="date" />
            <small class="text-muted">A partir de esta fecha el pago se considera atrasado. Por defecto, un mes después del inicio.</small>
          </div>
        </div>
        <div class="form-group" v-if="form.cliente_id && !(editing && originalHasCliente)">
          <label class="form-label">Importe a cobrar este mes</label>
          <input v-model.number="form.importe_final" class="form-control" type="number" step="0.01" min="0" />
          <small class="text-muted">Prorrateado según los días de alquiler este mes. Precio mensual completo: {{ formatMoney(form.precio_mensual) }}.</small>
        </div>
        <div class="form-group">
          <label class="form-label">Notas</label>
          <textarea v-model="form.notas" class="form-control" rows="2" placeholder="Notas opcionales..."></textarea>
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
      <p>¿Seguro que deseas eliminar el trastero <strong>{{ toDelete?.numero }}</strong>?</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showDelete = false">Cancelar</button>
        <button class="btn btn-danger" @click="doDelete" :disabled="saving">Eliminar</button>
      </div>
    </AppModal>

    <!-- Modal Dar de baja -->
    <AppModal v-model="showBaja" title="Dar de baja" size="sm">
      <div class="alert alert-danger" v-if="bajaError">{{ bajaError }}</div>
      <div v-if="!bajaInfo">
        <p>¿Dar de baja a <strong>{{ bajaTarget?.cliente?.nombre }} {{ bajaTarget?.cliente?.apellido }}</strong> del trastero <strong>{{ bajaTarget?.numero }}</strong>? La unidad quedará libre.</p>
      </div>
      <div v-else>
        <p>Antes de dar de baja, revisa lo pendiente para este trastero:</p>
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
      <div class="form-group">
        <label class="form-label">Importe a cobrar este mes</label>
        <input v-model.number="bajaImporteFinal" class="form-control" type="number" step="0.01" min="0" />
        <small class="text-muted">Prorrateado según los días de alquiler este mes. Precio mensual completo: {{ formatMoney(bajaTarget?.precio_mensual) }}.</small>
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
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useTrasterosStore } from '@/stores/trasteros'
import { useClientesStore } from '@/stores/clientes'
import { useTamanyosStore } from '@/stores/tamanyos'
import AppModal from '@/components/AppModal.vue'
import SearchSelect from '@/components/SearchSelect.vue'

const route = useRoute()
const store = useTrasterosStore()
const clientesStore = useClientesStore()
const tamanyosStore = useTamanyosStore()

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
const bajaImporteFinal = ref(0)

function formatDate(v) { return v ? v.split('T')[0] : '' }

// Días facturables entre max(fecha_inicio, día 1 del mes) y, según hastaFinDeMes,
// el último día del mes (asignación) u hoy (baja), entre días del mes.
function prorratearImporte(precioMensual, fechaInicioStr, hastaFinDeMes) {
  const hoy = new Date()
  const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
  const finMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0)
  const diasEnMes = finMes.getDate()
  const fechaInicio = fechaInicioStr ? new Date(fechaInicioStr + 'T00:00:00') : inicioMes
  const desde = fechaInicio > inicioMes ? fechaInicio : inicioMes
  const hasta = hastaFinDeMes ? finMes : hoy
  const diasFacturables = Math.max(0, Math.floor((hasta - desde) / 86400000) + 1)
  return Math.round((precioMensual * diasFacturables / diasEnMes) * 100) / 100
}

const emptyForm = () => ({
  numero: '', piso: '', tamanyo: '', precio_mensual: 0,
  cliente_id: null, fecha_inicio_alquiler: '', fecha_vencimiento: '', importe_final: null, notas: '',
})
const form = ref(emptyForm())

// Al fijar (o cambiar) la fecha de inicio, se sugiere un mes después como
// vencimiento por defecto — solo si el usuario no lo ha tocado ya a mano.
// Lo mismo con el importe a cobrar este mes, prorrateado hasta fin de mes.
watch(() => form.value.fecha_inicio_alquiler, (nueva, vieja) => {
  if (!nueva) return
  const sugeridaVieja = vieja ? sumarUnMes(vieja) : null
  if (!form.value.fecha_vencimiento || form.value.fecha_vencimiento === sugeridaVieja) {
    form.value.fecha_vencimiento = sumarUnMes(nueva)
  }
  const importeSugeridoViejo = vieja ? prorratearImporte(form.value.precio_mensual, vieja, true) : null
  if (form.value.importe_final == null || form.value.importe_final === importeSugeridoViejo) {
    form.value.importe_final = prorratearImporte(form.value.precio_mensual, nueva, true)
  }
})

// Al asignar un cliente a un trastero que no tenía (asignación nueva), se
// sugiere ya el importe prorrateado aunque la fecha de inicio siga vacía.
watch(() => form.value.cliente_id, (nuevo, viejo) => {
  if (nuevo && !viejo && !(editing.value && originalHasCliente.value)) {
    form.value.importe_final = prorratearImporte(form.value.precio_mensual, form.value.fecha_inicio_alquiler, true)
  }
})

function sumarUnMes(fechaStr) {
  const [y, m, d] = fechaStr.split('-').map(Number)
  const fecha = new Date(y, m, d) // m sin -1 ya suma un mes (mismo día, mes siguiente)
  return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`
}

const clienteOptions = computed(() =>
  clientesStore.clientes.map((c) => ({
    value: c.id,
    label: `${c.nombre} ${c.apellido} — ${c.dni}`,
  }))
)

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return store.trasteros.filter(
    (t) =>
      t.numero.toLowerCase().includes(q) ||
      t.piso.toLowerCase().includes(q) ||
      t.tamanyo.toLowerCase().includes(q)
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

function openEdit(t) {
  editing.value = true
  originalHasCliente.value = !!t.cliente_id
  form.value = {
    numero: t.numero,
    piso: t.piso,
    tamanyo: t.tamanyo,
    precio_mensual: t.precio_mensual,
    cliente_id: t.cliente_id ?? null,
    fecha_inicio_alquiler: formatDate(t.fecha_inicio_alquiler) ?? '',
    fecha_vencimiento: formatDate(t.fecha_vencimiento) ?? '',
    importe_final: null,
    notas: t.notas ?? '',
    _id: t.id,
  }
  formError.value = ''
  showModal.value = true
}

function confirmDelete(t) {
  toDelete.value = t
  showDelete.value = true
}

function openBaja(t) {
  bajaTarget.value = t
  bajaInfo.value = null
  bajaError.value = ''
  bajaImporteFinal.value = prorratearImporte(Number(t.precio_mensual), t.fecha_inicio_alquiler, false)
  showBaja.value = true
}

async function doBaja() {
  bajaSaving.value = true
  bajaError.value = ''
  try {
    const result = await store.darBajaTrastero(bajaTarget.value.id, !!bajaInfo.value, bajaImporteFinal.value)
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
      await store.updateTrastero(form.value._id, { ...form.value })
    } else {
      await store.createTrastero({ ...form.value })
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
    await store.deleteTrastero(toDelete.value.id)
    showDelete.value = false
  } catch (e) {
    alert(e.displayMessage || 'Error al eliminar')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (route.query.q) search.value = String(route.query.q)
  store.fetchTrasteros()
  clientesStore.fetchAllClientes()
  tamanyosStore.fetchTamanyos()
})
</script>
