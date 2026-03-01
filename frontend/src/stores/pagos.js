import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const usePagosStore = defineStore('pagos', () => {
  const pagos = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 15 })
  const loading = ref(false)
  const error = ref(null)

  async function fetchPagos(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/pagos-alquiler', { params })
      pagos.value = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
        from: data.from ?? 0,
        to: data.to ?? 0,
        per_page: data.per_page,
      }
    } catch (e) {
      error.value = e.displayMessage || 'Error al cargar pagos'
    } finally {
      loading.value = false
    }
  }

  async function crearPago(payload) {
    const { data } = await api.post('/pagos-alquiler', payload)
    pagos.value.unshift(data)
    return data
  }

  async function registrarPago(payload) {
    const { data } = await api.post('/pagos-alquiler/registrar-pago', payload)
    // Actualizar los registros modificados en la lista
    if (data.pagos_actualizados) {
      data.pagos_actualizados.forEach((updated) => {
        const idx = pagos.value.findIndex((p) => p.id === updated.id)
        if (idx !== -1) pagos.value[idx] = updated
      })
    }
    return data
  }

  async function deletePago(id) {
    await api.delete(`/pagos-alquiler/${id}`)
    pagos.value = pagos.value.filter((p) => p.id !== id)
  }

  return { pagos, pagination, loading, error, fetchPagos, crearPago, registrarPago, deletePago }
})
