import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useTamanyosStore = defineStore('tamanyos', () => {
  const tamanyos = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchTamanyos() {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/tamanyo-trasteros')
      tamanyos.value = data
    } catch (e) {
      error.value = e.displayMessage || 'Error al cargar los tamaños'
    } finally {
      loading.value = false
    }
  }

  async function createTamanyo(payload) {
    const { data } = await api.post('/tamanyo-trasteros', payload)
    tamanyos.value.push(data)
    return data
  }

  async function updateTamanyo(id, payload) {
    const { data } = await api.put(`/tamanyo-trasteros/${id}`, payload)
    const idx = tamanyos.value.findIndex((t) => t.id === id)
    if (idx !== -1) tamanyos.value[idx] = data
    return data
  }

  async function deleteTamanyo(id) {
    await api.delete(`/tamanyo-trasteros/${id}`)
    tamanyos.value = tamanyos.value.filter((t) => t.id !== id)
  }

  return { tamanyos, loading, error, fetchTamanyos, createTamanyo, updateTamanyo, deleteTamanyo }
})
