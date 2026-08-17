<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">✏️ Cambio de Precio Individual</h1>
      <router-link to="/mantenimiento/revision-precio" class="btn btn-secondary">← Volver</router-link>
    </div>

    <div class="card">
      <div v-if="loading" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Número</th>
              <th>Cliente</th>
              <th>Precio actual</th>
              <th>Nuevo precio</th>
              <th>Porcentaje</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="unidades.length === 0">
              <td colspan="7" class="text-center text-muted" style="padding:2rem">Sin trasteros ni pisos</td>
            </tr>
            <tr v-for="u in unidades" :key="`${u.tipo}-${u.id}`">
              <td>
                <span class="badge" :class="u.tipo === 'piso' ? 'badge-primary' : 'badge-info'">
                  {{ u.tipo === 'piso' ? '🏠 Piso' : '📦 Trastero' }}
                </span>
              </td>
              <td><strong>{{ u.numero }}</strong></td>
              <td>{{ u.cliente ? `${u.cliente.nombre} ${u.cliente.apellido}` : '—' }}</td>
              <td>{{ formatMoney(u.precio_mensual) }}</td>
              <td style="max-width:120px">
                <input
                  :value="nuevosPrecios[claveDe(u)]"
                  @input="onPrecioInput(u, $event.target.value)"
                  class="form-control" type="number" step="0.01" min="0"
                />
              </td>
              <td style="max-width:120px">
                <div style="display:flex; align-items:center; gap:0.25rem">
                  <input
                    :value="porcentajes[claveDe(u)]"
                    @input="onPorcentajeInput(u, $event.target.value)"
                    class="form-control" type="number" step="0.1"
                  />
                  <span>%</span>
                </div>
              </td>
              <td>
                <button
                  class="btn btn-primary btn-sm"
                  :disabled="!haCambiado(u) || guardandoIds.has(claveDe(u))"
                  @click="guardar(u)"
                >
                  {{ guardandoIds.has(claveDe(u)) ? 'Guardando...' : '💾 Guardar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/api'
import { useTrasterosStore } from '@/stores/trasteros'
import { usePisosStore } from '@/stores/pisos'

const trasterosStore = useTrasterosStore()
const pisosStore = usePisosStore()
const toast = useToast()

const loading = ref(false)
const guardandoIds = ref(new Set())
const nuevosPrecios = ref({})
const porcentajes = ref({})

const unidades = computed(() => [
  ...trasterosStore.trasteros.map((t) => ({ ...t, tipo: 'trastero' })),
  ...pisosStore.pisos.map((p) => ({ ...p, tipo: 'piso' })),
])

function claveDe(u) {
  return `${u.tipo}-${u.id}`
}

function formatMoney(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}

function initFila(u) {
  nuevosPrecios.value[claveDe(u)] = Number(u.precio_mensual)
  porcentajes.value[claveDe(u)] = 0
}

function onPrecioInput(u, valor) {
  const key = claveDe(u)
  const precio = valor === '' ? null : Number(valor)
  nuevosPrecios.value[key] = precio
  const actual = Number(u.precio_mensual)
  porcentajes.value[key] = actual && precio !== null ? Math.round(((precio - actual) / actual) * 1000) / 10 : 0
}

function onPorcentajeInput(u, valor) {
  const key = claveDe(u)
  const pct = valor === '' ? 0 : Number(valor)
  porcentajes.value[key] = pct
  const actual = Number(u.precio_mensual)
  nuevosPrecios.value[key] = Math.round(actual * (1 + pct / 100) * 100) / 100
}

function haCambiado(u) {
  const nuevo = nuevosPrecios.value[claveDe(u)]
  return nuevo !== null && nuevo !== undefined && nuevo !== '' && Number(nuevo) !== Number(u.precio_mensual)
}

async function guardar(u) {
  const key = claveDe(u)
  guardandoIds.value = new Set(guardandoIds.value).add(key)
  try {
    const { data } = await api.post('/revision-precio/individual', {
      tipo: u.tipo,
      referencia_id: u.id,
      precio_nuevo: nuevosPrecios.value[key],
    })
    toast.success(`Precio de ${u.tipo === 'piso' ? 'piso' : 'trastero'} ${u.numero} actualizado`)
    if (u.tipo === 'trastero') await trasterosStore.fetchTrasteros()
    else await pisosStore.fetchPisos()
    initFila({ ...u, precio_mensual: data.precio_mensual })
  } catch (e) {
    toast.error(e.displayMessage || 'No se pudo actualizar el precio')
  } finally {
    const next = new Set(guardandoIds.value)
    next.delete(key)
    guardandoIds.value = next
  }
}

onMounted(async () => {
  loading.value = true
  await Promise.all([trasterosStore.fetchTrasteros(), pisosStore.fetchPisos()])
  unidades.value.forEach(initFila)
  loading.value = false
})
</script>
