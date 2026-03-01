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
          <label class="form-label">Foto del DNI</label>
          <input type="file" class="form-control" accept="image/*,.pdf" @change="onFotoChange" />
          <small class="text-muted">JPG, PNG o PDF. Máx 5MB.</small>
          <div v-if="form.foto_dni_preview || (editing && currentFoto)" class="mt-1">
            <img v-if="form.foto_dni_preview" :src="form.foto_dni_preview" style="max-height:100px;border-radius:4px;margin-top:.5rem" />
            <small v-if="editing && currentFoto && !form.foto_dni_preview" class="text-muted">Foto actual guardada</small>
          </div>
        </div>

        <hr style="margin: 1rem 0; border-color: var(--gris-borde)" />
        <p style="font-size:.85rem;color:var(--gris-texto);margin-bottom:.75rem">
          <strong>Propiedades asociadas</strong> — Edita el trastero o piso directamente desde aquí.
        </p>

        <div v-if="editing" class="form-group">
          <label class="form-label">Trastero asignado</label>
          <SearchSelect
            v-model="form.trastero_id"
            :options="trasteroOptions"
            placeholder="Buscar trastero..."
            :allow-clear="true"
          />
          <small class="text-muted">Al cambiar, se actualiza el trastero automáticamente al guardar.</small>
        </div>

        <div v-if="editing" class="form-group">
          <label class="form-label">Piso asignado</label>
          <SearchSelect
            v-model="form.piso_id"
            :options="pisoOptions"
            placeholder="Buscar piso..."
            :allow-clear="true"
          />
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
import { ref, computed, watch, onMounted } from 'vue'
import { useClientesStore } from '@/stores/clientes'
import { useTrasterosStore } from '@/stores/trasteros'
import { usePisosStore } from '@/stores/pisos'
import AppModal from '@/components/AppModal.vue'
import AppPagination from '@/components/AppPagination.vue'
import SearchSelect from '@/components/SearchSelect.vue'
import api from '@/api'

const store = useClientesStore()
const trasterosStore = useTrasterosStore()
const pisosStore = usePisosStore()

const search = ref('')
const currentPage = ref(1)
const showModal = ref(false)
const showDelete = ref(false)
const editing = ref(false)
const saving = ref(false)
const formError = ref('')
const toDelete = ref(null)
const currentFoto = ref(null)

const apiBase = import.meta.env.VITE_API_BASE_URL
  ? import.meta.env.VITE_API_BASE_URL.replace('/api', '')
  : ''

function fotoUrl(ruta) {
  return `${apiBase}/storage/${ruta}`
}

const emptyForm = () => ({
  nombre: '', apellido: '', dni: '', telefono: '',
  foto_dni_file: null, foto_dni_preview: null,
  trastero_id: null, piso_id: null,
})
const form = ref(emptyForm())

// Opciones para selects (trasteros libres o con el mismo cliente)
const trasteroOptions = computed(() =>
  trasterosStore.trasteros
    .filter((t) => !t.cliente_id || (editing.value && form.value._clienteId && t.cliente_id === form.value._clienteId))
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
    store.fetchClientes({ search: val, page: 1 })
  }, 350)
})

function onPageChange(page) {
  currentPage.value = page
  store.fetchClientes({ search: search.value, page })
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
  formError.value = ''
  showModal.value = true
}

function openEdit(c) {
  editing.value = true
  const trastero = trasterosStore.trasteros.find((t) => t.cliente_id === c.id)
  const piso = pisosStore.pisos.find((p) => p.cliente_id === c.id)
  form.value = {
    nombre: c.nombre,
    apellido: c.apellido,
    dni: c.dni,
    telefono: c.telefono ?? '',
    foto_dni_file: null,
    foto_dni_preview: null,
    trastero_id: trastero?.id ?? null,
    piso_id: piso?.id ?? null,
    _id: c.id,
    _clienteId: c.id,
  }
  currentFoto.value = c.foto_dni
  formError.value = ''
  showModal.value = true
}

function confirmDelete(c) {
  toDelete.value = c
  showDelete.value = true
}

async function save() {
  formError.value = ''
  saving.value = true
  try {
    const fd = new FormData()
    fd.append('nombre', form.value.nombre)
    fd.append('apellido', form.value.apellido)
    fd.append('dni', form.value.dni)
    fd.append('telefono', form.value.telefono || '')
    if (form.value.foto_dni_file) {
      fd.append('foto_dni', form.value.foto_dni_file)
    }

    let cliente
    if (editing.value) {
      cliente = await store.updateCliente(form.value._id, fd)
    } else {
      cliente = await store.createCliente(fd)
    }

    // Actualizar asignaciones de trastero y piso
    if (editing.value) {
      // Desasignar trastero anterior si cambió
      const prevTrastero = trasterosStore.trasteros.find((t) => t.cliente_id === cliente.id)
      if (prevTrastero && prevTrastero.id !== form.value.trastero_id) {
        await api.put(`/trasteros/${prevTrastero.id}`, { ...prevTrastero, cliente_id: null, fecha_inicio_alquiler: null })
      }
      // Asignar nuevo trastero
      if (form.value.trastero_id) {
        const t = trasterosStore.trasteros.find((tt) => tt.id === form.value.trastero_id)
        if (t) await api.put(`/trasteros/${t.id}`, { ...t, cliente_id: cliente.id })
      }
      // Desasignar piso anterior si cambió
      const prevPiso = pisosStore.pisos.find((p) => p.cliente_id === cliente.id)
      if (prevPiso && prevPiso.id !== form.value.piso_id) {
        await api.put(`/pisos/${prevPiso.id}`, { ...prevPiso, cliente_id: null, fecha_inicio_alquiler: null })
      }
      // Asignar nuevo piso
      if (form.value.piso_id) {
        const p = pisosStore.pisos.find((pp) => pp.id === form.value.piso_id)
        if (p) await api.put(`/pisos/${p.id}`, { ...p, cliente_id: cliente.id })
      }
      // Refrescar trasteros y pisos
      await trasterosStore.fetchTrasteros()
      await pisosStore.fetchPisos()
    }

    showModal.value = false
    // Refrescar clientes para reflejar relaciones
    await store.fetchClientes({ search: search.value, page: currentPage.value })
  } catch (e) {
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
    await store.fetchClientes({ search: search.value, page: currentPage.value })
  } catch (e) {
    alert(e.displayMessage || 'Error al eliminar')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  store.fetchClientes({ page: 1 })
  trasterosStore.fetchTrasteros()
  pisosStore.fetchPisos()
})
</script>
