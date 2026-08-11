<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">⏳ Lista de Espera</h1>
      <button class="btn btn-primary" @click="openNew">+ Añadir</button>
    </div>

    <div class="card">
      <div v-if="store.loading" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="store.error" class="alert alert-danger">{{ store.error }}</div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Teléfono</th>
              <th>Tamaño de trastero</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.entradas.length === 0">
              <td colspan="5" class="text-center text-muted" style="padding:2rem">Nadie en la lista de espera</td>
            </tr>
            <tr v-for="e in store.entradas" :key="e.id">
              <td><strong>{{ e.nombre }}</strong></td>
              <td>{{ e.telefono }}</td>
              <td>{{ e.tamanyo }}</td>
              <td>{{ formatFecha(e.created_at) }}</td>
              <td>
                <div class="actions-cell">
                  <button class="btn btn-danger btn-sm" title="Eliminar de la lista" @click="confirmDelete(e)">🗑️ Eliminar</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal formulario -->
    <AppModal v-model="showModal" title="Añadir a la lista de espera" size="sm">
      <form @submit.prevent="save">
        <div class="alert alert-danger" v-if="formError">{{ formError }}</div>
        <div class="form-group">
          <label class="form-label">Nombre *</label>
          <input v-model="form.nombre" class="form-control" required />
        </div>
        <div class="form-group">
          <label class="form-label">Teléfono *</label>
          <input v-model="form.telefono" class="form-control" required placeholder="6XXXXXXXX" />
        </div>
        <div class="form-group">
          <label class="form-label">Tamaño de trastero *</label>
          <select v-model="form.tamanyo" class="form-control" required>
            <option value="" disabled>Selecciona un tamaño...</option>
            <option v-for="t in tamanyosStore.tamanyos" :key="t.id" :value="t.nombre">{{ t.nombre }}</option>
          </select>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Guardando...' : 'Añadir' }}
          </button>
        </div>
      </form>
    </AppModal>

    <!-- Confirm delete -->
    <AppModal v-model="showDelete" title="Confirmar eliminación" size="sm">
      <p>¿Quitar a <strong>{{ toDelete?.nombre }}</strong> de la lista de espera?</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showDelete = false">Cancelar</button>
        <button class="btn btn-danger" @click="doDelete" :disabled="saving">Eliminar</button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useListaEsperaStore } from '@/stores/listaEspera'
import { useTamanyosStore } from '@/stores/tamanyos'
import AppModal from '@/components/AppModal.vue'

const store = useListaEsperaStore()
const tamanyosStore = useTamanyosStore()

const showModal = ref(false)
const showDelete = ref(false)
const saving = ref(false)
const formError = ref('')
const toDelete = ref(null)

const emptyForm = () => ({ nombre: '', telefono: '', tamanyo: '' })
const form = ref(emptyForm())

function formatFecha(f) {
  if (!f) return '—'
  return new Date(f).toLocaleDateString('es-ES')
}

function openNew() {
  form.value = emptyForm()
  formError.value = ''
  showModal.value = true
}

function confirmDelete(e) {
  toDelete.value = e
  showDelete.value = true
}

async function save() {
  formError.value = ''
  saving.value = true
  try {
    await store.createEntrada({ ...form.value })
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
    await store.deleteEntrada(toDelete.value.id)
    showDelete.value = false
  } catch (e) {
    alert(e.displayMessage || 'Error al eliminar')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  store.fetchEntradas()
  tamanyosStore.fetchTamanyos()
})
</script>
