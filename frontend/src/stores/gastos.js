import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useGastosStore = defineStore('gastos', () => {
  const gastos = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 15 })
  const loading = ref(false)
  const error = ref(null)

  async function fetchGastos(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/gastos', { params })
      gastos.value = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
        from: data.from ?? 0,
        to: data.to ?? 0,
        per_page: data.per_page,
      }
    } catch (e) {
      error.value = e.displayMessage || 'Error al cargar gastos'
    } finally {
      loading.value = false
    }
  }

  async function createGasto(formData) {
    const { data } = await api.post('/gastos', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    gastos.value.unshift(data)
    return data
  }

  async function updateGasto(id, payload) {
    const { data } = await api.put(`/gastos/${id}`, payload)
    const idx = gastos.value.findIndex((g) => g.id === id)
    if (idx !== -1) gastos.value[idx] = data
    return data
  }

  async function deleteGasto(id) {
    await api.delete(`/gastos/${id}`)
    gastos.value = gastos.value.filter((g) => g.id !== id)
  }

  async function registrarPagoGasto(id, payload) {
    const { data } = await api.post(`/gastos/${id}/pago`, payload)
    const idx = gastos.value.findIndex((g) => g.id === id)
    if (idx !== -1) gastos.value[idx] = data
    return data
  }

  async function subirImagenes(id, formData) {
    const { data } = await api.post(`/gastos/${id}/imagenes`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const idx = gastos.value.findIndex((g) => g.id === id)
    if (idx !== -1) {
      gastos.value[idx].imagenes = [...(gastos.value[idx].imagenes || []), ...data.imagenes]
    }
    return data
  }

  async function eliminarImagen(gastoId, imagenId) {
    await api.delete(`/gastos/${gastoId}/imagenes/${imagenId}`)
    const idx = gastos.value.findIndex((g) => g.id === gastoId)
    if (idx !== -1) {
      gastos.value[idx].imagenes = gastos.value[idx].imagenes.filter((i) => i.id !== imagenId)
    }
  }

  return {
    gastos, pagination, loading, error,
    fetchGastos, createGasto, updateGasto, deleteGasto,
    registrarPagoGasto, subirImagenes, eliminarImagen,
  }
})
