<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">💰 Generar Pagos Mensuales</h1>
    </div>

    <div class="card" style="max-width: 540px">
      <div class="card-header" style="border-bottom: 1px solid var(--gris-borde); margin-bottom: 1.25rem">
        <strong>Generación de pagos de alquiler</strong>
      </div>

      <p style="color: var(--gris-texto); font-size: .92rem; margin-bottom: 1.25rem">
        Este proceso crea los registros de pago mensual para todos los trasteros y pisos
        que actualmente tengan un cliente asignado. Si el pago del mes ya existe, no se
        duplica.
      </p>

      <div class="form-row" style="margin-bottom: 1.25rem">
        <div class="form-group">
          <label class="form-label">Mes</label>
          <select v-model="mes" class="form-control">
            <option v-for="(nombre, idx) in MESES" :key="idx + 1" :value="idx + 1">
              {{ nombre }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Año</label>
          <input
            v-model.number="anyo"
            class="form-control"
            type="number"
            min="2000"
            max="2100"
            style="max-width: 110px"
          />
        </div>
      </div>

      <!-- Resultado -->
      <div v-if="resultado" :class="resultado.ok ? 'alert alert-success' : 'alert alert-danger'" style="margin-bottom: 1.25rem">
        <strong>{{ resultado.ok ? '✅' : '❌' }}</strong>
        {{ resultado.mensaje || resultado.error }}
        <div v-if="resultado.ok" style="margin-top: .4rem; font-size: .88rem; color: inherit; opacity: .85">
          <span v-if="resultado.creados > 0">
            Pagos nuevos creados: <strong>{{ resultado.creados }}</strong> &nbsp;·&nbsp;
          </span>
          Total registros en {{ MESES[resultado.mes - 1] }} {{ resultado.anyo }}: <strong>{{ resultado.total }}</strong>
        </div>
      </div>

      <div v-if="error" class="alert alert-danger" style="margin-bottom: 1.25rem">
        ❌ {{ error }}
      </div>

      <!-- Confirmación -->
      <div v-if="!confirming" style="display:flex; gap: .75rem; align-items: center">
        <button class="btn btn-primary" @click="confirming = true" :disabled="loading">
          ▶ Generar pagos de {{ MESES[mes - 1] }} {{ anyo }}
        </button>
      </div>

      <div v-else style="background: #fff8e1; border: 1px solid #f5c518; border-radius: 6px; padding: 1rem; margin-top: .5rem">
        <p style="margin: 0 0 .85rem; font-size: .95rem">
          ⚠️ ¿Confirmas la generación de pagos para
          <strong>{{ MESES[mes - 1] }} {{ anyo }}</strong>?
          Los pagos ya existentes no se duplicarán.
        </p>
        <div style="display:flex; gap: .75rem">
          <button class="btn btn-secondary" @click="confirming = false" :disabled="loading">Cancelar</button>
          <button class="btn btn-primary" @click="ejecutar" :disabled="loading">
            <span v-if="loading">⏳ Generando...</span>
            <span v-else>✅ Confirmar y generar</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/api'

const MESES = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
]

const ahora = new Date()
const mes   = ref(ahora.getMonth() + 1)
const anyo  = ref(ahora.getFullYear())

const loading    = ref(false)
const confirming = ref(false)
const resultado  = ref(null)
const error      = ref('')

async function ejecutar() {
  loading.value   = true
  resultado.value = null
  error.value     = ''
  try {
    const res = await api.post('/mantenimiento/generar-pagos', {
      mes:  mes.value,
      anyo: anyo.value,
    })
    resultado.value = res.data
  } catch (e) {
    const msg = e.response?.data?.error || e.response?.data?.message || 'Error al generar pagos.'
    error.value = msg
  } finally {
    loading.value   = false
    confirming.value = false
  }
}
</script>
