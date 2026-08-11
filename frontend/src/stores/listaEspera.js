import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useListaEsperaStore = defineStore('listaEspera', () => {
  const entradas = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchEntradas() {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/lista-espera')
      entradas.value = data
    } catch (e) {
      error.value = e.displayMessage || 'Error al cargar la lista de espera'
    } finally {
      loading.value = false
    }
  }

  async function createEntrada(payload) {
    const { data } = await api.post('/lista-espera', payload)
    entradas.value.push(data)
    return data
  }

  async function deleteEntrada(id) {
    await api.delete(`/lista-espera/${id}`)
    entradas.value = entradas.value.filter((e) => e.id !== id)
  }

  return { entradas, loading, error, fetchEntradas, createEntrada, deleteEntrada }
})
