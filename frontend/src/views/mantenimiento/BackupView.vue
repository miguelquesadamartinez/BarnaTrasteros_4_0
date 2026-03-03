<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">🗄️ Backup / Restauración de Base de Datos</h1>
    </div>

    <!-- Generar backup -->
    <div class="card" style="margin-bottom:1.5rem">
      <div class="card-header" style="border-bottom:1px solid var(--gris-borde); margin-bottom:1.25rem">
        <strong>Generar backup</strong>
      </div>
      <p style="color:var(--gris-texto); font-size:.92rem; margin-bottom:1.25rem">
        Genera un volcado comprimido de la base de datos y lo guarda en la carpeta
        <code>mysql_bk</code>. El backup nocturno automático se ejecuta cada día a las
        <strong>23:00</strong>.
      </p>
      <div v-if="backupResult" :class="backupResult.ok ? 'alert alert-success' : 'alert alert-danger'" style="margin-bottom:1rem">
        {{ backupResult.ok ? '✅' : '❌' }} {{ backupResult.mensaje || backupResult.error }}
      </div>
      <button class="btn btn-primary" :disabled="backedUp" @click="generarBackup">
        <span v-if="backedUp">⏳ Generando...</span>
        <span v-else>💾 Generar backup ahora</span>
      </button>
    </div>

    <!-- Lista de backups y restauración -->
    <div class="card">
      <div class="card-header" style="border-bottom:1px solid var(--gris-borde); margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center">
        <strong>Backups disponibles</strong>
        <button class="btn btn-secondary btn-sm" @click="cargarBackups">🔄 Actualizar</button>
      </div>

      <div v-if="loadingBackups" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="backups.length === 0" class="text-center text-muted" style="padding:1.5rem 0">
        Sin backups disponibles en <code>mysql_bk</code>.
      </div>
      <table v-else>
        <thead>
          <tr>
            <th>Archivo</th>
            <th>Fecha</th>
            <th>Tamaño</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="b in backups" :key="b.filename" :class="{ 'selected-row': selectedBackup === b.filename }">
            <td style="font-size:.85rem; font-family:monospace">{{ b.filename }}</td>
            <td>{{ b.fecha }}</td>
            <td>{{ b.size_kb }} KB</td>
            <td>
              <div style="display:flex; gap:.4rem">
                <button class="btn btn-warning btn-sm" @click="pedirRestaurar(b.filename)">♻️ Restaurar</button>
                <button class="btn btn-danger btn-sm" @click="pedirEliminar(b.filename)">🗑️ Eliminar</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="restoreResult" :class="restoreResult.ok ? 'alert alert-success' : 'alert alert-danger'" style="margin-top:1rem">
        {{ restoreResult.ok ? '✅' : '❌' }} {{ restoreResult.mensaje || restoreResult.error }}
      </div>
    </div>

    <!-- Modal confirmación restauración -->
    <AppModal v-model="showConfirmRestore" title="⚠️ Confirmar restauración" size="sm">
      <p>¿Seguro que quieres restaurar la base de datos desde:</p>
      <p style="font-family:monospace; font-size:.85rem; word-break:break-all; background:#f5f5f5; padding:.5rem; border-radius:4px">
        {{ selectedBackup }}
      </p>
      <p class="text-danger" style="font-size:.9rem">
        <strong>Esta acción sobreescribirá todos los datos actuales.</strong>
      </p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showConfirmRestore = false">Cancelar</button>
        <button class="btn btn-danger" :disabled="restoring" @click="confirmarRestaurar">
          {{ restoring ? '⏳ Restaurando...' : '✅ Sí, restaurar' }}
        </button>
      </div>
    </AppModal>

    <!-- Modal confirmación eliminación -->
    <AppModal v-model="showConfirmDelete" title="🗑️ Confirmar eliminación" size="sm">
      <p>¿Eliminar el backup:</p>
      <p style="font-family:monospace; font-size:.85rem; word-break:break-all; background:#f5f5f5; padding:.5rem; border-radius:4px">
        {{ selectedBackup }}
      </p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showConfirmDelete = false">Cancelar</button>
        <button class="btn btn-danger" :disabled="deleting" @click="confirmarEliminar">
          {{ deleting ? '⏳ Eliminando...' : '🗑️ Sí, eliminar' }}
        </button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import AppModal from '@/components/AppModal.vue'

const backups            = ref([])
const loadingBackups     = ref(false)
const backedUp           = ref(false)
const restoring          = ref(false)
const deleting           = ref(false)
const backupResult       = ref(null)
const restoreResult      = ref(null)
const selectedBackup     = ref(null)
const showConfirmRestore = ref(false)
const showConfirmDelete  = ref(false)

async function cargarBackups() {
  loadingBackups.value = true
  try {
    const { data } = await api.get('/mantenimiento/backups')
    backups.value = data
  } catch {
    backups.value = []
  } finally {
    loadingBackups.value = false
  }
}

async function generarBackup() {
  backedUp.value    = true
  backupResult.value = null
  try {
    const { data } = await api.post('/mantenimiento/backup')
    backupResult.value = data
    await cargarBackups()
  } catch (e) {
    backupResult.value = { ok: false, error: e.displayMessage || 'Error al generar el backup.' }
  } finally {
    backedUp.value = false
  }
}

function pedirRestaurar(filename) {
  selectedBackup.value  = filename
  restoreResult.value   = null
  showConfirmRestore.value = true
}

function pedirEliminar(filename) {
  selectedBackup.value = filename
  restoreResult.value  = null
  showConfirmDelete.value = true
}

async function confirmarEliminar() {
  deleting.value = true
  try {
    await api.delete('/mantenimiento/backup', { data: { filename: selectedBackup.value } })
    showConfirmDelete.value = false
    await cargarBackups()
  } catch (e) {
    restoreResult.value     = { ok: false, error: e.displayMessage || 'Error al eliminar el backup.' }
    showConfirmDelete.value = false
  } finally {
    deleting.value = false
  }
}

async function confirmarRestaurar() {
  restoring.value = true
  try {
    const { data } = await api.post('/mantenimiento/restore', { filename: selectedBackup.value })
    restoreResult.value      = data
    showConfirmRestore.value = false
  } catch (e) {
    restoreResult.value      = { ok: false, error: e.displayMessage || 'Error al restaurar.' }
    showConfirmRestore.value = false
  } finally {
    restoring.value = false
  }
}

onMounted(cargarBackups)
</script>

<style scoped>
.selected-row { background: #fff8e1; }
</style>
