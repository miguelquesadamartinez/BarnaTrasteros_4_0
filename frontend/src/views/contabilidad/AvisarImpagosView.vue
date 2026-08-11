<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">📣 Avisar Impagos</h1>
      <button class="btn btn-primary" :disabled="loading || pagos.length === 0" @click="showConfirm = true">
        🔔 Enviar aviso a todos
      </button>
    </div>

    <div class="card">
      <div v-if="loading" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="error" class="alert alert-danger">{{ error }}</div>
      <template v-else>
        <div class="stats-grid stats-3" style="padding:1.5rem 1.5rem 0">
          <div class="stat-card">
            <div class="stat-value">{{ pagos.length }}</div>
            <div class="stat-label">Pagos pendientes</div>
          </div>
          <div class="stat-card stat-danger">
            <div class="stat-value" style="font-size:1.2rem">{{ formatMoney(totalPendiente) }}</div>
            <div class="stat-label">Total pendiente</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ clientesUnicos }}</div>
            <div class="stat-label">Clientes afectados</div>
          </div>
        </div>

        <div class="table-wrapper" style="padding:1.5rem">
          <table>
            <thead>
              <tr>
                <th>Cliente</th>
                <th>Unidad</th>
                <th>Mes/Año</th>
                <th>Estado</th>
                <th>Pendiente</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="pagos.length === 0">
                <td colspan="5" class="text-center text-muted" style="padding:2rem">No hay pagos pendientes</td>
              </tr>
              <tr v-for="p in pagos" :key="p.id">
                <td>{{ p.cliente ? `${p.cliente.nombre} ${p.cliente.apellido}` : '—' }}</td>
                <td>{{ p.tipo === 'piso' ? '🏠' : '📦' }} {{ p.numero ?? p.referencia_id }}</td>
                <td>{{ mesNombre(p.mes) }} {{ p.anyo }}</td>
                <td><span class="badge" :class="estadoBadge(p.estado)">{{ p.estado }}</span></td>
                <td class="text-danger"><strong>{{ formatMoney(calcPendiente(p)) }}</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- Confirmación de envío -->
    <AppModal v-model="showConfirm" title="Confirmar envío masivo" size="sm">
      <p>
        Vas a enviar un email recordando el pago pendiente a
        <strong>{{ clientesUnicos }}</strong> cliente(s), por un total de
        <strong class="text-danger">{{ formatMoney(totalPendiente) }}</strong> en <strong>{{ pagos.length }}</strong> pagos.
      </p>
      <p class="text-muted" style="font-size:.85rem">Se envía un email por cada pago pendiente, aunque el aviso automático diario ya se haya enviado antes.</p>
      <div v-if="resultado" class="alert alert-success">{{ resultado }}</div>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showConfirm = false" :disabled="enviando">Cancelar</button>
        <button class="btn btn-primary" :disabled="enviando" @click="enviarATodos">
          {{ enviando ? 'Enviando...' : 'Enviar a todos' }}
        </button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import AppModal from '@/components/AppModal.vue'

const pagos = ref([])
const loading = ref(false)
const error = ref('')
const showConfirm = ref(false)
const enviando = ref(false)
const resultado = ref('')

const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
function mesNombre(m) { return MESES[m] || m }

function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}

function calcPendiente(p) {
  return Math.max(0, +p.importe_total - +p.pagado)
}

function estadoBadge(e) {
  return { pendiente: 'badge-danger', parcial: 'badge-warning' }[e] || 'badge-muted'
}

const totalPendiente = computed(() => pagos.value.reduce((s, p) => s + calcPendiente(p), 0))
const clientesUnicos = computed(() => new Set(pagos.value.map((p) => p.cliente_id)).size)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/pagos-alquiler', { params: { estado: 'pendiente,parcial', per_page: 1000 } })
    pagos.value = data.data
  } catch (e) {
    error.value = e.displayMessage || 'Error al cargar los pagos pendientes'
  } finally {
    loading.value = false
  }
}

async function enviarATodos() {
  enviando.value = true
  resultado.value = ''
  try {
    const { data } = await api.post('/pagos-alquiler/avisar-impagos-todos')
    resultado.value = `Se han enviado ${data.enviados} de ${data.total} avisos.`
  } catch (e) {
    resultado.value = ''
    alert(e.displayMessage || 'Error al enviar los avisos')
  } finally {
    enviando.value = false
  }
}

onMounted(load)
</script>
