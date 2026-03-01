<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">📐 Tamaños de Trasteros</h1>
      <button class="btn btn-primary" @click="openNew">+ Nuevo Tamaño</button>
    </div>

    <div class="card">
      <div v-if="store.loading" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="store.error" class="alert alert-danger">{{ store.error }}</div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Orden</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.tamanyos.length === 0">
              <td colspan="5" class="text-center text-muted" style="padding:2rem">Sin tamaños definidos</td>
            </tr>
            <tr v-for="t in store.tamanyos" :key="t.id">
              <td>{{ t.orden }}</td>
              <td><strong>{{ t.nombre }}</strong></td>
              <td>{{ t.descripcion || '—' }}</td>
              <td>
                <span class="badge" :class="t.activo ? 'badge-success' : 'badge-muted'">
                  {{ t.activo ? 'Sí' : 'No' }}
                </span>
              </td>
              <td>
                <div class="actions-cell">
                  <button class="btn btn-warning btn-sm" title="Editar tamaño" @click="openEdit(t)">✏️ Editar</button>
                  <button class="btn btn-danger btn-sm" title="Eliminar tamaño" @click="confirmDelete(t)">🗑️ Eliminar</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal formulario -->
    <AppModal v-model="showModal" :title="editing ? 'Editar Tamaño' : 'Nuevo Tamaño'" size="sm">
      <form @submit.prevent="save">
        <div class="alert alert-danger" v-if="formError">{{ formError }}</div>
        <div class="form-group">
          <label class="form-label">Nombre *</label>
          <input v-model="form.nombre" class="form-control" required placeholder="Ej: Pequeño (5m²)" />
        </div>
        <div class="form-group">
          <label class="form-label">Descripción</label>
          <input v-model="form.descripcion" class="form-control" placeholder="Descripción opcional" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Orden</label>
            <input v-model.number="form.orden" class="form-control" type="number" min="0" />
          </div>
          <div class="form-group" style="display:flex;align-items:center;gap:.5rem;padding-top:1.6rem">
            <input id="activo-check" v-model="form.activo" type="checkbox" />
            <label for="activo-check" class="form-label" style="margin:0">Activo</label>
          </div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear') }}
          </button>
        </div>
      </form>
    </AppModal>

    <!-- Confirm delete -->
    <AppModal v-model="showDelete" title="Confirmar eliminación" size="sm">
      <p>¿Eliminar el tamaño <strong>{{ toDelete?.nombre }}</strong>?</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showDelete = false">Cancelar</button>
        <button class="btn btn-danger" @click="doDelete" :disabled="saving">Eliminar</button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useTamanyosStore } from '@/stores/tamanyos'
import AppModal from '@/components/AppModal.vue'

const store = useTamanyosStore()

const showModal = ref(false)
const showDelete = ref(false)
const editing = ref(false)
const saving = ref(false)
const formError = ref('')
const toDelete = ref(null)

const emptyForm = () => ({ nombre: '', descripcion: '', orden: 0, activo: true })
const form = ref(emptyForm())

function openNew() {
  editing.value = false
  form.value = emptyForm()
  formError.value = ''
  showModal.value = true
}

function openEdit(t) {
  editing.value = true
  form.value = { nombre: t.nombre, descripcion: t.descripcion || '', orden: t.orden, activo: !!t.activo, _id: t.id }
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
      await store.updateTamanyo(form.value._id, { ...form.value })
    } else {
      await store.createTamanyo({ ...form.value })
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
    await store.deleteTamanyo(toDelete.value.id)
    showDelete.value = false
  } catch (e) {
    alert(e.displayMessage || 'Error al eliminar')
  } finally {
    saving.value = false
  }
}

onMounted(() => store.fetchTamanyos())
</script>
