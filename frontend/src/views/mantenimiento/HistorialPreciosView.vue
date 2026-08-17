<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">🕘 Historial de Precios</h1>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="text-muted">{{ pagination.total }} cambios registrados</span>
      </div>

      <div v-if="loading" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="error" class="alert alert-danger">{{ error }}</div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Número</th>
              <th>Precio anterior</th>
              <th>Precio nuevo</th>
              <th>Diferencia</th>
              <th>Porcentaje</th>
              <th>Motivo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="cambios.length === 0">
              <td colspan="8" class="text-center text-muted" style="padding:2rem">Sin cambios de precio registrados</td>
            </tr>
            <tr v-for="c in cambios" :key="c.id">
              <td>{{ formatFecha(c.created_at) }}</td>
              <td>
                <span class="badge" :class="c.tipo === 'piso' ? 'badge-primary' : 'badge-info'">
                  {{ c.tipo === 'piso' ? '🏠 Piso' : '📦 Trastero' }}
                </span>
              </td>
              <td><strong>{{ c.numero ?? c.referencia_id }}</strong></td>
              <td>{{ formatMoney(c.precio_anterior) }}</td>
              <td>{{ formatMoney(c.precio_nuevo) }}</td>
              <td :class="diferencia(c) >= 0 ? 'text-success' : 'text-danger'">
                <strong>{{ diferencia(c) >= 0 ? '+' : '' }}{{ formatMoney(diferencia(c)) }}</strong>
              </td>
              <td :class="diferencia(c) >= 0 ? 'text-success' : 'text-danger'">
                <strong v-if="c.porcentaje !== null && c.porcentaje !== undefined">{{ c.porcentaje >= 0 ? '+' : '' }}{{ Number(c.porcentaje).toFixed(2) }}%</strong>
                <span v-else class="text-muted">—</span>
              </td>
              <td>{{ c.motivo || 'Cambio individual' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :total="pagination.total"
        :from="pagination.from"
        :to="pagination.to"
        @change="load"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import AppPagination from '@/components/AppPagination.vue'

const cambios = ref([])
const loading = ref(false)
const error = ref('')
const pagination = ref({ total: 0, last_page: 1, from: 0, to: 0, current_page: 1 })

function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}

function formatFecha(f) {
  if (!f) return '—'
  return new Date(f).toLocaleString('es-ES')
}

function diferencia(c) {
  return Number(c.precio_nuevo) - Number(c.precio_anterior)
}

async function load(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/revision-precio/historial', { params: { page, per_page: 20 } })
    cambios.value = data.data
    pagination.value = { total: data.total, last_page: data.last_page, from: data.from, to: data.to, current_page: data.current_page }
  } catch (e) {
    error.value = e.displayMessage || 'Error al cargar el historial'
  } finally {
    loading.value = false
  }
}

onMounted(() => load(1))
</script>
