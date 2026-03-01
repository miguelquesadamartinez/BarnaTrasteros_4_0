<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">🧾 Facturas del Mes</h1>
    </div>

    <!-- Selector de período -->
    <div class="card" style="margin-bottom:1rem">
      <div class="filters-bar">
        <div class="filter-item">
          <span class="filter-label">Mes</span>
          <select v-model="mes" class="form-control" @change="cargar">
            <option v-for="m in 12" :key="m" :value="m">{{ mesNombre(m) }}</option>
          </select>
        </div>
        <div class="filter-item">
          <span class="filter-label">Año</span>
          <input v-model.number="anyo" class="form-control" type="number" min="2020" max="2099" @change="cargar" style="max-width:100px" />
        </div>
        <button class="btn btn-primary" style="margin-top:auto" @click="descargarTodas" :disabled="loading || !facturas.length">
          📥 Descargar todas ({{ facturas.length }})
        </button>
      </div>
    </div>

    <!-- Estado de carga -->
    <div v-if="loading" class="spinner-wrapper"><div class="spinner"></div></div>
    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-else-if="!facturas.length" class="card">
      <div style="text-align:center;padding:2.5rem;color:var(--gris-texto)">
        <div style="font-size:2.5rem;margin-bottom:.75rem">📭</div>
        <p>No hay clientes con facturación pendiente para <strong>{{ mesNombre(mes) }} {{ anyo }}</strong>.</p>
        <small class="text-muted">Solo se incluyen clientes con la opción "Necesita factura" activada que tengan pagos ese mes.</small>
      </div>
    </div>

    <!-- Lista de facturas -->
    <div v-else>
      <p class="text-muted" style="margin-bottom:.75rem;font-size:.875rem">
        {{ facturas.length }} factura(s) generadas para <strong>{{ mesNombre(mes) }} {{ anyo }}</strong>
      </p>
      <div class="facturas-grid">
        <div
          v-for="f in facturas"
          :key="f.cliente.id"
          class="factura-card"
        >
          <div class="factura-card-header">
            <div class="factura-card-num">Factura #{{ String(f.cliente.id).padStart(4, '0') }}/{{ anyo }}</div>
            <span class="badge" :class="estadoClass(f)">{{ estadoLabel(f) }}</span>
          </div>
          <div class="factura-card-body">
            <div class="factura-cliente">
              <strong>{{ f.cliente.nombre }} {{ f.cliente.apellido }}</strong>
              <small>DNI: {{ f.cliente.dni }}</small>
              <small v-if="f.cliente.direccion">{{ f.cliente.direccion }}</small>
              <small v-if="f.cliente.ciudad">{{ f.cliente.codigo_postal }} {{ f.cliente.ciudad }}</small>
            </div>
            <div class="factura-conceptos">
              <div v-for="p in f.pagos" :key="p.id" class="factura-concepto-row">
                <span>{{ p.tipo === 'piso' ? '🏠' : '📦' }} {{ p.tipo === 'piso' ? 'Piso' : 'Trastero' }} #{{ p.referencia_id }}</span>
                <span>{{ formatMoney(p.importe_total) }}</span>
              </div>
            </div>
          </div>
          <div class="factura-card-footer">
            <div class="factura-totales">
              <span class="text-muted" style="font-size:.8rem">Total</span>
              <strong>{{ formatMoney(f.importe_total) }}</strong>
            </div>
            <button class="btn btn-primary btn-sm" @click="descargar(f)">
              📄 Descargar PDF
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import { usePdfRecibo } from '@/composables/usePdfRecibo'

const { generarFacturaCliente } = usePdfRecibo()

const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
function mesNombre(m) { return MESES[m] || m }

const hoy = new Date()
const mes  = ref(hoy.getMonth() + 1)
const anyo = ref(hoy.getFullYear())

const loading  = ref(false)
const error    = ref(null)
const facturas = ref([])

function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}

function estadoClass(f) {
  const pagado = f.total_pagado >= f.importe_total
  const parcial = f.total_pagado > 0 && f.total_pagado < f.importe_total
  return pagado ? 'badge-success' : parcial ? 'badge-warning' : 'badge-danger'
}
function estadoLabel(f) {
  const pagado = f.total_pagado >= f.importe_total
  const parcial = f.total_pagado > 0 && f.total_pagado < f.importe_total
  return pagado ? 'Pagado' : parcial ? 'Parcial' : 'Pendiente'
}

async function cargar() {
  loading.value = true
  error.value   = null
  facturas.value = []
  try {
    const { data } = await api.get('/facturas', { params: { mes: mes.value, anyo: anyo.value } })
    facturas.value = data.facturas
  } catch (e) {
    error.value = e.displayMessage || 'Error al cargar facturas'
  } finally {
    loading.value = false
  }
}

function descargar(f) {
  generarFacturaCliente(f, mes.value, anyo.value)
}

function descargarTodas() {
  facturas.value.forEach((f) => generarFacturaCliente(f, mes.value, anyo.value))
}

onMounted(cargar)
</script>

<style scoped>
.facturas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

.factura-card {
  background: var(--blanco);
  border: 1px solid var(--gris-borde);
  border-radius: var(--radio);
  box-shadow: var(--sombra);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: var(--transicion);
}

.factura-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,.14);
  transform: translateY(-1px);
}

.factura-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .75rem 1rem;
  background: var(--rojo-claro);
  border-bottom: 1px solid var(--rojo-borde);
}

.factura-card-num {
  font-size: .8rem;
  font-weight: 700;
  color: var(--rojo);
  letter-spacing: .04em;
}

.factura-card-body {
  padding: 1rem;
  flex: 1;
}

.factura-cliente {
  display: flex;
  flex-direction: column;
  gap: .18rem;
  margin-bottom: .75rem;
}

.factura-cliente strong {
  font-size: 1rem;
}

.factura-cliente small {
  font-size: .78rem;
  color: var(--gris-texto);
}

.factura-conceptos {
  background: var(--gris-bg);
  border-radius: var(--radio);
  padding: .5rem .75rem;
}

.factura-concepto-row {
  display: flex;
  justify-content: space-between;
  font-size: .85rem;
  padding: .25rem 0;
  border-bottom: 1px solid var(--gris-borde);
}

.factura-concepto-row:last-child {
  border-bottom: none;
}

.factura-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .75rem 1rem;
  border-top: 1px solid var(--gris-borde);
  background: var(--gris-bg);
}

.factura-totales {
  display: flex;
  flex-direction: column;
}

.factura-totales strong {
  font-size: 1.1rem;
  color: var(--rojo);
}
</style>
