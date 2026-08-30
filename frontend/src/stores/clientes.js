import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useClientesStore = defineStore('clientes', () => {
  const clientes = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 15 })
  const loading = ref(false)
  const error = ref(null)

  const archivados = ref([])
  const paginationArchivados = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 15 })
  const loadingArchivados = ref(false)
  const errorArchivados = ref(null)

  async function fetchClientes(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/clientes', { params })
      clientes.value = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
        from: data.from ?? 0,
        to: data.to ?? 0,
        per_page: data.per_page,
      }
    } catch (e) {
      error.value = e.displayMessage || 'Error al cargar clientes'
    } finally {
      loading.value = false
    }
  }

   async function fetchAllClientes(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/clientes/list-all')
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

  async function fetchArchivados(params = {}) {
    loadingArchivados.value = true
    errorArchivados.value = null
    try {
      const { data } = await api.get('/clientes/archivados', { params })
      archivados.value = data.data
      paginationArchivados.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
        from: data.from ?? 0,
        to: data.to ?? 0,
        per_page: data.per_page,
      }
    } catch (e) {
      errorArchivados.value = e.displayMessage || 'Error al cargar clientes archivados'
    } finally {
      loadingArchivados.value = false
    }
  }

  async function archivarCliente(id, force = false) {
    try {
      const { data } = await api.post(`/clientes/${id}/archivar`, { force })
      clientes.value = clientes.value.filter((c) => c.id !== id)
      return { ok: true, data }
    } catch (e) {
      if (e.response?.status === 409) {
        return { ok: false, pendientes: e.response.data }
      }
      throw e
    }
  }

  async function desarchivarCliente(id) {
    const { data } = await api.post(`/clientes/${id}/desarchivar`)
    archivados.value = archivados.value.filter((c) => c.id !== id)
    return data
  }

  return {
    clientes, pagination, loading, error,
    fetchClientes, fetchAllClientes, createCliente, updateCliente, deleteCliente,
    archivados, paginationArchivados, loadingArchivados, errorArchivados,
    fetchArchivados, archivarCliente, desarchivarCliente,
  }
})
