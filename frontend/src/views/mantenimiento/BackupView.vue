<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">💾 Copias de seguridad</h1>
      <div class="header-actions">
        <input ref="inputSubida" type="file" accept=".zip,.gz" style="display:none" @change="subirCopia" />
        <button class="btn btn-secondary" :disabled="subiendo" @click="inputSubida.click()">
          {{ subiendo ? `⏳ Subiendo ${progresoSubida}%` : '⬆️ Subir copia' }}
        </button>
        <button class="btn btn-secondary" :disabled="cargando" @click="cargar">🔄 Actualizar</button>
      </div>
    </div>

    <!-- Resumen -->
    <div class="stats-grid bk-stats">
      <div class="stat-card">
        <div class="stat-label">Última copia</div>
        <div class="stat-value bk-stat-value">{{ resumen.ultima ? haceCuanto(resumen.ultima.fecha) : 'Ninguna' }}</div>
        <div class="stat-sub" v-if="resumen.ultima">{{ TIPOS[resumen.ultima.tipo].nombre }} · {{ fechaES(resumen.ultima.fecha, { conHora: true }) }}</div>
        <div class="stat-sub bk-aviso" v-if="avisoSinCompleta">⚠️ {{ avisoSinCompleta }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Copias guardadas</div>
        <div class="stat-value bk-stat-value">{{ resumen.total ?? 0 }}</div>
        <div class="stat-sub">{{ tamano(resumen.bytes) }} en total</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Copia automática</div>
        <div class="stat-value bk-stat-value">🌙 {{ resumen.hora_automatica || '23:00' }}</div>
        <div class="stat-sub">Completa, cada noche · se guardan las últimas {{ resumen.retencion }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Datos actuales</div>
        <div class="stat-value bk-stat-value">{{ actual.bd?.clientes ?? '—' }} <small>clientes</small></div>
        <div class="stat-sub">{{ actual.bd?.filas ?? 0 }} registros · {{ actual.archivos?.total ?? 0 }} archivos ({{ tamano(actual.archivos?.bytes) }})</div>
      </div>
    </div>

    <!-- Crear copia -->
    <div class="card">
      <div class="card-header"><span class="card-title">Crear una copia ahora</span></div>
      <div class="bk-tipos">
        <button
          v-for="(t, clave) in TIPOS"
          :key="clave"
          type="button"
          class="bk-tipo"
          :class="{ activo: tipoNuevo === clave }"
          @click="tipoNuevo = clave"
        >
          <span class="bk-tipo-icono" :style="{ background: t.fondo }">{{ t.icono }}</span>
          <span class="bk-tipo-texto">
            <strong>{{ t.nombre }} <span v-if="clave === 'completo'" class="badge badge-success">Recomendada</span></strong>
            <small>{{ t.descripcion }}</small>
            <small class="bk-tipo-datos">{{ datosTipo(clave) }}</small>
          </span>
        </button>
      </div>
      <div class="form-actions" style="justify-content:flex-start">
        <button class="btn btn-primary" :disabled="creando" @click="crearCopia">
          {{ creando ? '⏳ Creando copia...' : `💾 Crear copia ${TIPOS[tipoNuevo].nombre.toLowerCase()}` }}
        </button>
      </div>
    </div>

    <!-- Listado -->
    <div class="card">
      <div class="card-header bk-list-header">
        <span class="card-title">Copias disponibles</span>
        <div class="bk-filtros">
          <button
            v-for="f in filtros"
            :key="f.clave"
            class="bk-filtro"
            :class="{ activo: filtro === f.clave }"
            @click="filtro = f.clave"
          >{{ f.nombre }} <span>{{ f.cuenta }}</span></button>
        </div>
      </div>

      <div v-if="cargando && !backups.length" class="spinner-wrapper"><div class="spinner"></div></div>
      <div v-else-if="!visibles.length" class="text-muted" style="padding:1.5rem;text-align:center">No hay copias de este tipo.</div>

      <div v-else class="bk-lista">
        <div v-for="b in visibles" :key="b.filename" class="bk-item">
          <div class="bk-item-icono" :style="{ background: TIPOS[b.tipo].fondo }">{{ TIPOS[b.tipo].icono }}</div>
          <div class="bk-item-info">
            <div class="bk-item-titulo">
              <strong>{{ TIPOS[b.tipo].nombre }}</strong>
              <span class="bk-item-fecha">{{ fechaES(b.fecha, { conHora: true }) }} · {{ haceCuanto(b.fecha) }}</span>
            </div>
            <div class="bk-item-badges">
              <span class="badge" :class="ORIGENES[b.origen].clase">{{ ORIGENES[b.origen].nombre }}</span>
              <span class="badge badge-muted">{{ tamano(b.bytes) }}</span>
              <span v-if="verificaciones[b.filename]?.estado === 'ok'" class="badge badge-success">✔ Íntegra</span>
              <span v-else-if="verificaciones[b.filename]?.estado === 'mal'" class="badge badge-danger" :title="verificaciones[b.filename].problemas.join(' ')">✖ Dañada</span>
            </div>
            <div class="bk-item-contenido">{{ contenido(b) }}</div>
            <div class="bk-item-archivo">{{ b.filename }}</div>
          </div>
          <div class="bk-item-acciones">
            <button class="btn btn-danger btn-sm" title="Restaurar esta copia" @click="pedirRestaurar(b)">♻️ Restaurar</button>
            <a class="btn btn-secondary btn-sm" :href="urlDescarga(b)" title="Descargar al ordenador">⬇️</a>
            <button
              class="btn btn-secondary btn-sm"
              title="Comprobar que la copia se puede leer entera"
              :disabled="verificaciones[b.filename]?.estado === 'comprobando'"
              @click="verificar(b)"
            >{{ verificaciones[b.filename]?.estado === 'comprobando' ? '⏳' : '🔍' }}</button>
            <button
              v-if="resumen.email_configurado"
              class="btn btn-secondary btn-sm"
              :title="b.bytes > resumen.email_max_mb * 1048576 ? `Supera ${resumen.email_max_mb} MB: descárgala` : 'Enviar por email'"
              :disabled="enviando === b.filename || b.bytes > resumen.email_max_mb * 1048576"
              @click="enviarEmail(b)"
            >{{ enviando === b.filename ? '⏳' : '📧' }}</button>
            <button class="btn btn-secondary btn-sm" title="Eliminar" @click="pedirEliminar(b)">🗑️</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal restaurar -->
    <AppModal v-model="showRestaurar" title="♻️ Restaurar copia" size="md">
      <template v-if="seleccionada">
        <div class="bk-modal-copia">
          <span class="bk-item-icono" :style="{ background: TIPOS[seleccionada.tipo].fondo }">{{ TIPOS[seleccionada.tipo].icono }}</span>
          <div>
            <strong>{{ TIPOS[seleccionada.tipo].nombre }} · {{ fechaES(seleccionada.fecha, { conHora: true }) }}</strong>
            <div class="text-muted" style="font-size:.85rem">{{ contenido(seleccionada) }}</div>
          </div>
        </div>

        <p class="form-label" style="margin-top:1rem">¿Qué quieres restaurar?</p>
        <label class="bk-check" :class="{ deshabilitado: !seleccionada.contiene.bd }">
          <input type="checkbox" v-model="restaurarBd" :disabled="!seleccionada.contiene.bd" />
          <span>🗄️ <strong>Base de datos</strong> — clientes, unidades, pagos, gastos, fianzas…
            <em v-if="!seleccionada.contiene.bd">(esta copia no la incluye)</em></span>
        </label>
        <label class="bk-check" :class="{ deshabilitado: !seleccionada.contiene.archivos }">
          <input type="checkbox" v-model="restaurarArchivos" :disabled="!seleccionada.contiene.archivos" />
          <span>🖼️ <strong>Archivos</strong> — fotos de DNI, contratos e imágenes de gastos
            <em v-if="!seleccionada.contiene.archivos">(esta copia no los incluye)</em></span>
        </label>
        <label class="bk-check bk-check-seguridad">
          <input type="checkbox" v-model="copiaPrevia" />
          <span>🛡️ Guardar antes una copia del estado actual <strong>(recomendado)</strong>, para poder deshacer la restauración.</span>
        </label>

        <div class="alert alert-warning" v-if="restaurarBd || restaurarArchivos">
          ⚠️ Se <strong>sustituirán {{ partesTexto }}</strong> actuales por los de esta copia.
          <span v-if="!copiaPrevia"> Sin copia previa, lo actual se perderá.</span>
        </div>

        <div class="form-group">
          <label class="form-label">Escribe <strong>RESTAURAR</strong> para confirmar</label>
          <input v-model="textoConfirmacion" class="form-control" placeholder="RESTAURAR" autocomplete="off" />
        </div>

        <div v-if="errorRestaurar" class="alert alert-danger">{{ errorRestaurar }}</div>

        <div class="form-actions">
          <button class="btn btn-secondary" :disabled="restaurando" @click="showRestaurar = false">Cancelar</button>
          <button class="btn btn-danger" :disabled="!puedeRestaurar || restaurando" @click="confirmarRestaurar">
            {{ restaurando ? '⏳ Restaurando... no cierres esta ventana' : '♻️ Restaurar' }}
          </button>
        </div>
      </template>
    </AppModal>

    <!-- Modal resultado restauración -->
    <AppModal v-model="showResultado" title="✅ Restauración completada" size="sm">
      <p>Se ha restaurado <strong>{{ resultadoTexto }}</strong> correctamente.</p>
      <p v-if="resultado?.copia_seguridad" class="text-muted" style="font-size:.88rem">
        El estado anterior se guardó en una copia <span class="badge badge-warning">Antes de restaurar</span> por si necesitas deshacerlo.
      </p>
      <div class="form-actions">
        <button class="btn btn-primary" @click="recargarApp">Recargar la aplicación</button>
      </div>
    </AppModal>

    <!-- Modal eliminar -->
    <AppModal v-model="showEliminar" title="🗑️ Eliminar copia" size="sm">
      <p v-if="seleccionada">¿Eliminar la copia <strong>{{ TIPOS[seleccionada.tipo].nombre.toLowerCase() }}</strong> del {{ fechaES(seleccionada.fecha, { conHora: true }) }}? No se puede deshacer.</p>
      <div class="form-actions">
        <button class="btn btn-secondary" @click="showEliminar = false">Cancelar</button>
        <button class="btn btn-danger" :disabled="eliminando" @click="confirmarEliminar">{{ eliminando ? '⏳ Eliminando...' : '🗑️ Eliminar' }}</button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/api'
import AppModal from '@/components/AppModal.vue'
import { fechaES } from '@/utils/format'

const toast = useToast()
const apiBase = import.meta.env.VITE_API_BASE_URL || '/api'

const TIPOS = {
  completo: { nombre: 'Completa', icono: '🗂️', fondo: '#e8f5e9', descripcion: 'Base de datos + archivos (DNI, contratos, imágenes de gastos). Con ella se recupera todo.' },
  bd:       { nombre: 'Solo base de datos', icono: '🗄️', fondo: '#e3f2fd', descripcion: 'Clientes, unidades, pagos, gastos, fianzas… sin los archivos.' },
  archivos: { nombre: 'Solo archivos', icono: '🖼️', fondo: '#fff3e0', descripcion: 'Fotos de DNI, contratos en PDF e imágenes de gastos.' },
}
const ORIGENES = {
  manual:    { nombre: 'Manual', clase: 'badge-info' },
  auto:      { nombre: 'Automática', clase: 'badge-success' },
  seguridad: { nombre: 'Antes de restaurar', clase: 'badge-warning' },
  subido:    { nombre: 'Subida', clase: 'badge-primary' },
  antigua:   { nombre: 'Antigua', clase: 'badge-muted' },
}

const backups = ref([])
const resumen = ref({})
const actual = ref({})
const cargando = ref(false)
const tipoNuevo = ref('completo')
const creando = ref(false)
const filtro = ref('todas')
const verificaciones = ref({})
const enviando = ref(null)
const inputSubida = ref(null)
const subiendo = ref(false)
const progresoSubida = ref(0)

const seleccionada = ref(null)
const showRestaurar = ref(false)
const showResultado = ref(false)
const showEliminar = ref(false)
const restaurarBd = ref(true)
const restaurarArchivos = ref(true)
const copiaPrevia = ref(true)
const textoConfirmacion = ref('')
const restaurando = ref(false)
const errorRestaurar = ref('')
const resultado = ref(null)
const eliminando = ref(false)

const filtros = computed(() => {
  const n = (fn) => backups.value.filter(fn).length
  return [
    { clave: 'todas', nombre: 'Todas', cuenta: backups.value.length },
    { clave: 'completo', nombre: '🗂️ Completas', cuenta: n((b) => b.tipo === 'completo') },
    { clave: 'bd', nombre: '🗄️ Base de datos', cuenta: n((b) => b.tipo === 'bd') },
    { clave: 'archivos', nombre: '🖼️ Archivos', cuenta: n((b) => b.tipo === 'archivos') },
    { clave: 'auto', nombre: '🌙 Automáticas', cuenta: n((b) => b.origen === 'auto') },
    { clave: 'seguridad', nombre: '🛡️ Antes de restaurar', cuenta: n((b) => b.origen === 'seguridad') },
  ].filter((f) => f.clave === 'todas' || f.cuenta > 0)
})

const visibles = computed(() => {
  const f = filtro.value
  if (f === 'todas') return backups.value
  if (TIPOS[f]) return backups.value.filter((b) => b.tipo === f)
  return backups.value.filter((b) => b.origen === f)
})

const avisoSinCompleta = computed(() => {
  const c = resumen.value.ultima_completa
  if (!c) return 'Aún no hay ninguna copia completa'
  const dias = (Date.now() - new Date(c.fecha.replace(' ', 'T'))) / 86400000
  return dias > 2 ? `La última copia completa es de hace ${Math.floor(dias)} días` : ''
})

const partesTexto = computed(() =>
  [restaurarBd.value && 'la base de datos', restaurarArchivos.value && 'los archivos'].filter(Boolean).join(' y ')
)
const resultadoTexto = computed(() => {
  const r = resultado.value?.restaurado || {}
  return [r.bd && 'la base de datos', r.archivos && 'los archivos'].filter(Boolean).join(' y ')
})
const puedeRestaurar = computed(() =>
  (restaurarBd.value || restaurarArchivos.value) && textoConfirmacion.value.trim().toUpperCase() === 'RESTAURAR'
)

function tamano(bytes) {
  const b = Number(bytes) || 0
  if (b < 1024) return `${b} B`
  if (b < 1048576) return `${(b / 1024).toLocaleString('es-ES', { maximumFractionDigits: 1 })} KB`
  return `${(b / 1048576).toLocaleString('es-ES', { maximumFractionDigits: 1 })} MB`
}

function haceCuanto(fecha) {
  const seg = (Date.now() - new Date(String(fecha).replace(' ', 'T'))) / 1000
  if (seg < 60) return 'hace un momento'
  if (seg < 3600) return `hace ${Math.floor(seg / 60)} min`
  if (seg < 86400) return `hace ${Math.floor(seg / 3600)} h`
  const d = Math.floor(seg / 86400)
  return d === 1 ? 'ayer' : `hace ${d} días`
}

function contenido(b) {
  const partes = []
  const bd = b.resumen?.bd
  const ar = b.resumen?.archivos
  if (b.contiene.bd) partes.push(bd ? `${bd.clientes} clientes · ${bd.pagos} pagos · ${bd.gastos} gastos (${bd.filas} registros)` : 'Base de datos')
  if (b.contiene.archivos) partes.push(ar ? `${ar.total} archivos (${tamano(ar.bytes)})` : 'Archivos')
  return partes.join(' + ')
}

function datosTipo(clave) {
  const bd = actual.value.bd
  const ar = actual.value.archivos
  if (!bd || !ar) return ''
  const txtBd = `${bd.filas} registros`
  const txtAr = `${ar.total} archivos`
  return clave === 'completo' ? `Ahora: ${txtBd} + ${txtAr}` : clave === 'bd' ? `Ahora: ${txtBd}` : `Ahora: ${txtAr} (${tamano(ar.bytes)})`
}

function urlDescarga(b) {
  return `${apiBase}/backups/${encodeURIComponent(b.filename)}/descargar`
}

async function cargar() {
  cargando.value = true
  try {
    const { data } = await api.get('/backups')
    backups.value = data.backups
    resumen.value = data.resumen
    actual.value = data.actual
  } catch (e) {
    toast.error(e.displayMessage || 'No se pudieron cargar las copias.')
  } finally {
    cargando.value = false
  }
}

async function crearCopia() {
  creando.value = true
  try {
    const { data } = await api.post('/backups', { tipo: tipoNuevo.value })
    toast.success(`Copia ${TIPOS[data.backup.tipo].nombre.toLowerCase()} creada (${tamano(data.backup.bytes)}).`)
    filtro.value = 'todas'
    await cargar()
  } catch (e) {
    toast.error(e.displayMessage || 'No se pudo crear la copia.')
  } finally {
    creando.value = false
  }
}

async function verificar(b) {
  verificaciones.value = { ...verificaciones.value, [b.filename]: { estado: 'comprobando' } }
  try {
    const { data } = await api.post(`/backups/${encodeURIComponent(b.filename)}/verificar`)
    verificaciones.value = { ...verificaciones.value, [b.filename]: { estado: data.ok ? 'ok' : 'mal', problemas: data.problemas } }
    data.ok ? toast.success('La copia está íntegra y se puede restaurar.') : toast.error(`Copia dañada: ${data.problemas.join(' ')}`)
  } catch (e) {
    verificaciones.value = { ...verificaciones.value, [b.filename]: { estado: 'mal', problemas: [e.displayMessage] } }
    toast.error(e.displayMessage || 'No se pudo verificar la copia.')
  }
}

async function enviarEmail(b) {
  enviando.value = b.filename
  try {
    const { data } = await api.post(`/backups/${encodeURIComponent(b.filename)}/email`)
    toast.success(data.mensaje)
  } catch (e) {
    toast.error(e.displayMessage || 'No se pudo enviar la copia por email.')
  } finally {
    enviando.value = null
  }
}

async function subirCopia(ev) {
  const archivo = ev.target.files?.[0]
  ev.target.value = ''
  if (!archivo) return
  subiendo.value = true
  progresoSubida.value = 0
  try {
    const fd = new FormData()
    fd.append('archivo', archivo)
    const { data } = await api.post('/backups/subir', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (p) => { progresoSubida.value = p.total ? Math.round((p.loaded / p.total) * 100) : 0 },
    })
    toast.success(`Copia subida y verificada: ${TIPOS[data.backup.tipo].nombre.toLowerCase()} del ${fechaES(data.backup.fecha)}. Ya puedes restaurarla.`)
    filtro.value = 'todas'
    await cargar()
  } catch (e) {
    toast.error(e.displayMessage || 'No se pudo subir la copia.')
  } finally {
    subiendo.value = false
  }
}

function pedirRestaurar(b) {
  seleccionada.value = b
  restaurarBd.value = b.contiene.bd
  restaurarArchivos.value = b.contiene.archivos
  copiaPrevia.value = true
  textoConfirmacion.value = ''
  errorRestaurar.value = ''
  showRestaurar.value = true
}

async function confirmarRestaurar() {
  restaurando.value = true
  errorRestaurar.value = ''
  try {
    const { data } = await api.post(`/backups/${encodeURIComponent(seleccionada.value.filename)}/restaurar`, {
      bd: restaurarBd.value,
      archivos: restaurarArchivos.value,
      copia_previa: copiaPrevia.value,
    }, { timeout: 600000 })
    resultado.value = data
    showRestaurar.value = false
    showResultado.value = true
    await cargar()
  } catch (e) {
    errorRestaurar.value = e.displayMessage || 'No se pudo restaurar la copia.'
  } finally {
    restaurando.value = false
  }
}

function recargarApp() {
  // Tras restaurar, los datos que otras pantallas tuvieran en memoria son de antes.
  window.location.reload()
}

function pedirEliminar(b) {
  seleccionada.value = b
  showEliminar.value = true
}

async function confirmarEliminar() {
  eliminando.value = true
  try {
    await api.delete(`/backups/${encodeURIComponent(seleccionada.value.filename)}`)
    showEliminar.value = false
    toast.success('Copia eliminada.')
    await cargar()
  } catch (e) {
    toast.error(e.displayMessage || 'No se pudo eliminar la copia.')
  } finally {
    eliminando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.header-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.bk-stats { margin-bottom: 1.25rem; }
.bk-stat-value { font-size: 1.35rem; }
.bk-stat-value small { font-size: .8rem; font-weight: 400; color: var(--gris-texto); }
.bk-aviso { color: var(--naranja); font-weight: 600; margin-top: .25rem; }

.bk-tipos {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: .75rem;
  margin-bottom: 1rem;
}
.bk-tipo {
  display: flex;
  gap: .75rem;
  align-items: flex-start;
  text-align: left;
  background: #fff;
  border: 2px solid var(--gris-borde);
  border-radius: 10px;
  padding: .85rem;
  cursor: pointer;
  font-family: inherit;
  transition: var(--transicion);
}
.bk-tipo:hover { border-color: var(--rojo-borde); }
.bk-tipo.activo { border-color: var(--rojo); box-shadow: 0 0 0 3px var(--rojo-claro); }
.bk-tipo-icono, .bk-item-icono {
  flex: 0 0 auto;
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
}
.bk-tipo-texto { display: flex; flex-direction: column; gap: .2rem; }
.bk-tipo-texto small { color: var(--gris-texto); font-size: .8rem; line-height: 1.3; }
.bk-tipo-texto .bk-tipo-datos { color: var(--negro); font-weight: 600; }

.bk-list-header { flex-wrap: wrap; gap: .75rem; }
.bk-filtros { display: flex; gap: .35rem; flex-wrap: wrap; }
.bk-filtro {
  border: 1px solid var(--gris-borde);
  background: #fff;
  border-radius: 999px;
  padding: .25rem .7rem;
  font-size: .8rem;
  cursor: pointer;
  font-family: inherit;
}
.bk-filtro span { background: var(--gris-bg); border-radius: 999px; padding: 0 .4rem; margin-left: .2rem; }
.bk-filtro.activo { background: var(--rojo); border-color: var(--rojo); color: #fff; }
.bk-filtro.activo span { background: rgba(255,255,255,.25); }

.bk-lista { display: flex; flex-direction: column; gap: .6rem; }
.bk-item {
  display: flex;
  gap: .85rem;
  align-items: center;
  border: 1px solid var(--gris-borde);
  border-radius: 10px;
  padding: .75rem .9rem;
  background: #fff;
}
.bk-item:hover { border-color: var(--rojo-borde); }
.bk-item-info { flex: 1; min-width: 0; }
.bk-item-titulo { display: flex; gap: .5rem; align-items: baseline; flex-wrap: wrap; }
.bk-item-fecha { color: var(--gris-texto); font-size: .85rem; }
.bk-item-badges { display: flex; gap: .3rem; flex-wrap: wrap; margin: .3rem 0; }
.bk-item-contenido { font-size: .85rem; }
.bk-item-archivo { font-family: monospace; font-size: .72rem; color: #999; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bk-item-acciones { display: flex; gap: .3rem; flex-wrap: wrap; justify-content: flex-end; }
.bk-item-acciones a.btn { text-decoration: none; }

.bk-modal-copia { display: flex; gap: .75rem; align-items: center; background: var(--gris-bg); border-radius: 8px; padding: .7rem; }
.bk-check { display: flex; gap: .6rem; align-items: flex-start; padding: .55rem .7rem; border: 1px solid var(--gris-borde); border-radius: 8px; margin-bottom: .5rem; cursor: pointer; font-size: .9rem; }
.bk-check input { margin-top: .2rem; }
.bk-check.deshabilitado { opacity: .55; cursor: not-allowed; }
.bk-check em { color: var(--gris-texto); }
.bk-check-seguridad { background: #f1f8e9; border-color: #c5e1a5; }

@media (max-width: 640px) {
  .bk-item { flex-wrap: wrap; }
  .bk-item-acciones { width: 100%; justify-content: flex-start; }
}
</style>
