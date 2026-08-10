import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useFianzasStore = defineStore('fianzas', () => {
  const fianzas = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 15 })
  const loading = ref(false)
  const error = ref(null)

  async function fetchFianzas(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/fianzas', { params })
      fianzas.value = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
        from: data.from ?? 0,
        to: data.to ?? 0,
        per_page: data.per_page,
      }
    } catch (e) {
      error.value = e.displayMessage || 'Error al cargar fianzas'
    } finally {
      loading.value = false
    }
  }

  async function fetchFianzasCliente(clienteId) {
    const { data } = await api.get('/fianzas', { params: { cliente_id: clienteId, per_page: 100 } })
    return data.data
  }

  async function createFianza(payload) {
    const { data } = await api.post('/fianzas', payload)
    return data
  }

  async function updateFianza(id, payload) {
    const { data } = await api.put(`/fianzas/${id}`, payload)
    const idx = fianzas.value.findIndex((f) => f.id === id)
    if (idx !== -1) fianzas.value[idx] = data
    return data
  }

  async function deleteFianza(id) {
    await api.delete(`/fianzas/${id}`)
    fianzas.value = fianzas.value.filter((f) => f.id !== id)
  }

  return {
    fianzas, pagination, loading, error,
    fetchFianzas, fetchFianzasCliente, createFianza, updateFianza, deleteFianza,
  }
})
