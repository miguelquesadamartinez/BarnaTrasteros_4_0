import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const usePisosStore = defineStore('pisos', () => {
  const pisos = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchPisos(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/pisos', { params })
      pisos.value = data
    } catch (e) {
      error.value = e.displayMessage || 'Error al cargar pisos'
    } finally {
      loading.value = false
    }
  }

  async function createPiso(payload) {
    const { data } = await api.post('/pisos', payload)
    pisos.value.push(data)
    return data
  }

  async function updatePiso(id, payload) {
    const { data } = await api.put(`/pisos/${id}`, payload)
    const idx = pisos.value.findIndex((p) => p.id === id)
    if (idx !== -1) pisos.value[idx] = data
    return data
  }

  async function deletePiso(id) {
    await api.delete(`/pisos/${id}`)
    pisos.value = pisos.value.filter((p) => p.id !== id)
  }

  return { pisos, loading, error, fetchPisos, createPiso, updatePiso, deletePiso }
})
