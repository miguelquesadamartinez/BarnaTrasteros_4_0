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
                  <button class="btn btn-warning btn-sm" @click="openEdit(t)">✏️ Editar</button>
                  <button class="btn btn-danger btn-sm" @click="confirmDelete(t)">🗑️ Eliminar</button>
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
          />
        </div>
        <div class="form-group" v-if="form.cliente_id">
          <label class="form-label">Fecha inicio alquiler</label>
          <input v-model="form.fecha_inicio_alquiler" class="form-control" type="date" />
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTrasterosStore } from '@/stores/trasteros'
import { useClientesStore } from '@/stores/clientes'
import { useTamanyosStore } from '@/stores/tamanyos'
import AppModal from '@/components/AppModal.vue'
import SearchSelect from '@/components/SearchSelect.vue'

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

const emptyForm = () => ({
  numero: '', piso: '', tamanyo: '', precio_mensual: 0,
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
  form.value = {
    numero: t.numero,
    piso: t.piso,
    tamanyo: t.tamanyo,
    precio_mensual: t.precio_mensual,
    cliente_id: t.cliente_id ?? null,
    fecha_inicio_alquiler: t.fecha_inicio_alquiler ?? '',
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
  store.fetchTrasteros()
  clientesStore.fetchClientes()
  tamanyosStore.fetchTamanyos()
})
</script>
