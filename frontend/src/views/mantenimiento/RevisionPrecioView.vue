<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">📈 Revisión de Precio</h1>
    </div>

    <div class="card" style="padding:1.5rem">
      <h2 class="card-title" style="margin-top:0">Aplicar un porcentaje a todos los precios</h2>
      <p class="text-muted" style="font-size:.9rem">
        Sube o baja de golpe el precio mensual de todos los trasteros y/o pisos. Cada cambio queda registrado en el historial.
      </p>

      <div class="form-row">
        <div class="form-group" style="flex:0 0 160px">
          <label class="form-label">Porcentaje *</label>
          <input v-model.number="porcentaje" class="form-control" type="number" step="0.1" placeholder="Ej: 5 o -3" />
          <small class="text-muted">Positivo para subir, negativo para bajar</small>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;gap:1.25rem;padding-bottom:.4rem">
          <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
            <input type="checkbox" v-model="aplicarTrasteros" style="width:16px;height:16px" />
            📦 Trasteros ({{ trasterosStore.trasteros.length }})
          </label>
          <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
            <input type="checkbox" v-model="aplicarPisos" style="width:16px;height:16px" />
            🏠 Pisos ({{ pisosStore.pisos.length }})
          </label>
        </div>
      </div>

      <div class="alert alert-danger" v-if="formError">{{ formError }}</div>

      <div class="form-actions" style="justify-content:flex-start">
        <button
          class="btn btn-primary"
          :disabled="!porcentaje || (!aplicarTrasteros && !aplicarPisos)"
          @click="showConfirm = true"
        >
          Aplicar porcentaje
        </button>
      </div>
    </div>

    <div class="card" style="padding:1.5rem;margin-top:1.5rem">
      <h2 class="card-title" style="margin-top:0">Cambiar precios uno a uno</h2>
      <p class="text-muted" style="font-size:.9rem">Revisa y edita el precio mensual de cada trastero o piso individualmente.</p>
      <router-link to="/mantenimiento/revision-precio/individual" class="btn btn-secondary">✏️ Ir a cambio individual</router-link>
    </div>

    <!-- Confirmación -->
    <AppModal v-model="showConfirm" title="Confirmar cambio de precios" size="sm">
      <p>
        Vas a aplicar un <strong :class="porcentaje >= 0 ? 'text-success' : 'text-danger'">{{ porcentaje >= 0 ? '+' : '' }}{{ porcentaje }}%</strong>
        a
        <strong v-if="aplicarTrasteros">{{ trasterosStore.trasteros.length }} trasteros</strong>
        <span v-if="aplicarTrasteros && aplicarPisos"> y </span>
        <strong v-if="aplicarPisos">{{ pisosStore.pisos.length }} pisos</strong>.
      </p>
      <p class="text-muted" style="font-size:.85rem">Esta acción no se puede deshacer, aunque queda registrada en el historial de precios.</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showConfirm = false" :disabled="aplicando">Cancelar</button>
        <button class="btn btn-primary" :disabled="aplicando" @click="aplicarPorcentaje">
          {{ aplicando ? 'Aplicando...' : 'Confirmar' }}
        </button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/api'
import AppModal from '@/components/AppModal.vue'
import { useTrasterosStore } from '@/stores/trasteros'
import { usePisosStore } from '@/stores/pisos'

const trasterosStore = useTrasterosStore()
const pisosStore = usePisosStore()
const toast = useToast()

const porcentaje = ref(null)
const aplicarTrasteros = ref(true)
const aplicarPisos = ref(true)
const showConfirm = ref(false)
const aplicando = ref(false)
const formError = ref('')

async function aplicarPorcentaje() {
  aplicando.value = true
  formError.value = ''
  try {
    const { data } = await api.post('/revision-precio/todos', {
      porcentaje: porcentaje.value,
      trasteros: aplicarTrasteros.value,
      pisos: aplicarPisos.value,
    })
    toast.success(`${data.actualizados} precio(s) actualizado(s)`)
    showConfirm.value = false
    porcentaje.value = null
    await Promise.all([trasterosStore.fetchTrasteros(), pisosStore.fetchPisos()])
  } catch (e) {
    formError.value = e.displayMessage || 'Error al aplicar el porcentaje'
    showConfirm.value = false
  } finally {
    aplicando.value = false
  }
}

onMounted(() => {
  trasterosStore.fetchTrasteros()
  pisosStore.fetchPisos()
})
</script>
