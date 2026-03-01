<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Panel de Control</h1>
    </div>

    <div v-if="loading" class="spinner-wrapper">
      <div class="spinner"></div>
    </div>

    <template v-else>
      <div class="stats-grid">
        <div class="stat-card">
          <span class="stat-label">Trasteros Alquilados</span>
          <span class="stat-value">{{ resumen.trasteros?.alquilados ?? 0 }}</span>
          <span class="stat-sub">de {{ resumen.trasteros?.total ?? 0 }} total · {{ resumen.trasteros?.libres ?? 0 }} libres</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Pisos Alquilados</span>
          <span class="stat-value">{{ resumen.pisos?.alquilados ?? 0 }}</span>
          <span class="stat-sub">de {{ resumen.pisos?.total ?? 0 }} total · {{ resumen.pisos?.libres ?? 0 }} libres</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Pagos Pendientes</span>
          <span class="stat-value">{{ formatMoney(resumen.pagos_pendientes) }}</span>
          <span class="stat-sub">Alquileres por cobrar</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Gastos Pendientes</span>
          <span class="stat-value">{{ formatMoney(resumen.gastos_pendientes) }}</span>
          <span class="stat-sub">Facturas por pagar</span>
        </div>
      </div>

      <div class="card">
        <h2 class="card-title">Acceso Rápido</h2>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: .75rem;">
          <router-link to="/trasteros" class="btn btn-primary">📦 Trasteros</router-link>
          <router-link to="/pisos" class="btn btn-primary">🏠 Pisos</router-link>
          <router-link to="/clientes" class="btn btn-primary">👥 Clientes</router-link>
          <router-link to="/pagos" class="btn btn-success">💶 Pagos Alquiler</router-link>
          <router-link to="/gastos" class="btn btn-warning">⚡ Gastos</router-link>
          <router-link to="/relatorios" class="btn btn-info">📊 Relatorios</router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const resumen = ref({})
const loading = ref(true)

function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}

onMounted(async () => {
  try {
    const { data } = await api.get('/relatorios/resumen-general')
    resumen.value = data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})
</script>
