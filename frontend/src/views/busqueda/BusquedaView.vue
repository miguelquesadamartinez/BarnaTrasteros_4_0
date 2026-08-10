<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">🔍 Búsqueda</h1>
    </div>

    <div class="card" style="padding:1.5rem">
      <input
        v-model="q"
        class="form-control"
        type="text"
        placeholder="Buscar cliente, trastero, piso, fianza, gasto o pago..."
        style="font-size:1.1rem;padding:.75rem 1rem"
        autofocus
      />

      <div v-if="loading" class="spinner-wrapper" style="margin-top:1.5rem"><div class="spinner"></div></div>

      <div v-else-if="q.trim().length > 0 && q.trim().length < 2" class="text-muted" style="margin-top:1rem">
        Escribe al menos 2 caracteres...
      </div>

      <div v-else-if="q.trim().length >= 2 && !hayResultados" class="text-muted" style="margin-top:1rem">
        Sin resultados para «{{ q }}».
      </div>

      <div v-else-if="q.trim().length >= 2" style="margin-top:1.5rem;display:flex;flex-direction:column;gap:1.5rem">
        <div v-for="grupo in gruposConResultados" :key="grupo.key">
          <h3 style="font-size:.95rem;color:var(--gris);margin-bottom:.5rem">
            {{ grupo.label }} ({{ grupo.items.length }})
          </h3>
          <div style="display:flex;flex-direction:column;gap:.4rem">
            <button
              v-for="item in grupo.items"
              :key="item.id"
              type="button"
              class="resultado-row"
              @click="irAResultado(grupo.key, item)"
            >
              <span class="resultado-titulo">{{ grupo.titulo(item) }}</span>
              <span class="resultado-detalle text-muted">{{ grupo.detalle(item) }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'

const router = useRouter()

const q = ref('')
const loading = ref(false)
const resultados = ref({ clientes: [], trasteros: [], pisos: [], fianzas: [], gastos: [], pagos: [] })

function nombreCliente(cliente) {
  return cliente ? `${cliente.nombre} ${cliente.apellido}` : 'Sin cliente'
}

const grupos = [
  {
    key: 'clientes',
    label: 'Clientes',
    ruta: '/clientes',
    titulo: (c) => `${c.nombre} ${c.apellido}`,
    detalle: (c) => [c.dni, c.telefono].filter(Boolean).join(' · '),
  },
  {
    key: 'trasteros',
    label: 'Trasteros',
    ruta: '/trasteros',
    titulo: (t) => `📦 Trastero ${t.numero}`,
    detalle: (t) => `${t.piso ? `Piso ${t.piso} · ` : ''}${nombreCliente(t.cliente)}`,
  },
  {
    key: 'pisos',
    label: 'Pisos',
    ruta: '/pisos',
    titulo: (p) => `🏠 Piso ${p.numero}`,
    detalle: (p) => nombreCliente(p.cliente),
  },
  {
    key: 'fianzas',
    label: 'Fianzas',
    ruta: '/fianzas',
    titulo: (f) => `💰 Fianza #${f.numero ?? f.id}`,
    detalle: (f) => `${nombreCliente(f.cliente)} · ${f.devuelta ? 'Devuelta' : 'Activa'}`,
  },
  {
    key: 'gastos',
    label: 'Gastos',
    ruta: '/gastos',
    titulo: (g) => `🧾 ${g.descripcion}`,
    detalle: (g) => `${g.tipo} · ${g.estado}`,
  },
  {
    key: 'pagos',
    label: 'Pagos de alquiler',
    ruta: '/pagos',
    titulo: (p) => `💳 Pago ${p.mes}/${p.anyo}`,
    detalle: (p) => nombreCliente(p.cliente),
  },
]

const gruposConResultados = computed(() =>
  grupos
    .map((g) => ({ ...g, items: resultados.value[g.key] || [] }))
    .filter((g) => g.items.length > 0)
)

const hayResultados = computed(() => gruposConResultados.value.length > 0)

function irAResultado(grupoKey, item) {
  const grupo = grupos.find((g) => g.key === grupoKey)
  const conFiltro = ['clientes', 'trasteros', 'pisos', 'fianzas'].includes(grupoKey)
  router.push(conFiltro ? { path: grupo.ruta, query: { q: q.value.trim() } } : { path: grupo.ruta })
}

let debounceTimer = null
watch(q, (val) => {
  clearTimeout(debounceTimer)
  const texto = val.trim()
  if (texto.length < 2) {
    resultados.value = { clientes: [], trasteros: [], pisos: [], fianzas: [], gastos: [], pagos: [] }
    return
  }
  debounceTimer = setTimeout(async () => {
    loading.value = true
    try {
      const { data } = await api.get('/busqueda', { params: { q: texto } })
      resultados.value = data
    } finally {
      loading.value = false
    }
  }, 300)
})
</script>

<style scoped>
.resultado-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  width: 100%;
  text-align: left;
  padding: .6rem .8rem;
  border: 1px solid var(--gris-borde);
  border-radius: 6px;
  background: white;
  cursor: pointer;
  transition: border-color .15s, background .15s;
}
.resultado-row:hover {
  border-color: var(--rojo);
  background: #fafafa;
}
.resultado-titulo {
  font-weight: 500;
}
.resultado-detalle {
  font-size: .85rem;
  white-space: nowrap;
}
</style>
