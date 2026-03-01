import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useTrasterosStore = defineStore('trasteros', () => {
  const trasteros = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchTrasteros(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/trasteros', { params })
      trasteros.value = data
    } catch (e) {
      error.value = e.displayMessage || 'Error al cargar trasteros'
    } finally {
      loading.value = false
    }
  }

  async function createTrastero(payload) {
    const { data } = await api.post('/trasteros', payload)
    trasteros.value.push(data)
    return data
  }

  async function updateTrastero(id, payload) {
    const { data } = await api.put(`/trasteros/${id}`, payload)
    const idx = trasteros.value.findIndex((t) => t.id === id)
    if (idx !== -1) trasteros.value[idx] = data
    return data
  }

  async function deleteTrastero(id) {
    await api.delete(`/trasteros/${id}`)
    trasteros.value = trasteros.value.filter((t) => t.id !== id)
  }

  return { trasteros, loading, error, fetchTrasteros, createTrastero, updateTrastero, deleteTrastero }
})
