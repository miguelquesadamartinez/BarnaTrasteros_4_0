<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">🗄️ Clientes Archivados</h1>
    </div>

    <div class="card">
      <div class="card-header">
        <input
          v-model="search"
          class="form-control"
          style="max-width: 320px"
          placeholder="Buscar por nombre, apellido o DNI..."
        />
        <span class="text-muted">{{ store.paginationArchivados.total }} archivados</span>
      </div>

      <div v-if="store.loadingArchivados" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="store.errorArchivados" class="alert alert-danger">{{ store.errorArchivados }}</div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>DNI</th>
              <th>Teléfono</th>
              <th>Archivado el</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.archivados.length === 0">
              <td colspan="5" class="text-center text-muted" style="padding:2rem">No hay clientes archivados</td>
            </tr>
            <tr v-for="c in store.archivados" :key="c.id">
              <td><strong>{{ c.nombre }} {{ c.apellido }}</strong></td>
              <td>{{ c.dni }}</td>
              <td>{{ c.telefono || '—' }}</td>
              <td>{{ formatDate(c.archivado_at) }}</td>
              <td>
                <div class="actions-cell">
                  <button class="btn btn-primary btn-sm" title="Desarchivar cliente" @click="doUnarchive(c)" :disabled="restoringId === c.id">
                    {{ restoringId === c.id ? 'Restaurando...' : '↩️ Desarchivar' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <AppPagination
        :current-page="store.paginationArchivados.current_page"
        :last-page="store.paginationArchivados.last_page"
        :total="store.paginationArchivados.total"
        :from="store.paginationArchivados.from"
        :to="store.paginationArchivados.to"
        @change="onPageChange"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useClientesStore } from '@/stores/clientes'
import AppPagination from '@/components/AppPagination.vue'

const toast = useToast()
const store = useClientesStore()

const search = ref('')
const currentPage = ref(1)
const restoringId = ref(null)

function formatDate(v) { return v ? v.split('T')[0] : '' }

function load() {
  store.fetchArchivados({ search: search.value, page: currentPage.value })
}

function onPageChange(page) {
  currentPage.value = page
  load()
}

async function doUnarchive(c) {
  restoringId.value = c.id
  try {
    await store.desarchivarCliente(c.id)
    toast.success(`${c.nombre} ${c.apellido} vuelve a estar activo`)
    load()
  } catch (e) {
    toast.error(e.displayMessage || 'No se pudo desarchivar el cliente')
  } finally {
    restoringId.value = null
  }
}

let searchTimeout
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    load()
  }, 350)
})

onMounted(load)
</script>
