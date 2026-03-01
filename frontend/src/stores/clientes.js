import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useClientesStore = defineStore('clientes', () => {
  const clientes = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchClientes(search = '') {
    loading.value = true
    error.value = null
    try {
      const params = search ? { search } : {}
      const { data } = await api.get('/clientes', { params })
      clientes.value = data
    } catch (e) {
      error.value = e.displayMessage || 'Error al cargar clientes'
    } finally {
      loading.value = false
    }
  }

  async function createCliente(formData) {
    const { data } = await api.post('/clientes', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    clientes.value.push(data)
    return data
  }

  async function updateCliente(id, formData) {
    // Laravel no soporta PUT con multipart, usar POST con _method=PUT
    formData.append('_method', 'PUT')
    const { data } = await api.post(`/clientes/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const idx = clientes.value.findIndex((c) => c.id === id)
    if (idx !== -1) clientes.value[idx] = data
    return data
  }

  async function deleteCliente(id) {
    await api.delete(`/clientes/${id}`)
    clientes.value = clientes.value.filter((c) => c.id !== id)
  }

  return { clientes, loading, error, fetchClientes, createCliente, updateCliente, deleteCliente }
})
