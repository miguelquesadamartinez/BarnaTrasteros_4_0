// Formatos de presentación comunes (es-ES) para que fechas, importes y
// porcentajes se vean igual en todas las pantallas.

const EURO = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' })
const DECIMAL2 = new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

/** Fecha en dd/mm/aaaa. Acepta 'YYYY-MM-DD', 'YYYY-MM-DD HH:MM:SS' o ISO con zona. */
export function fechaES(v, { conHora = false } = {}) {
  if (!v) return ''
  const s = String(v)
  // Fecha pura: se formatea tal cual, sin pasar por Date (evita desfases de zona horaria).
  const soloFecha = s.match(/^(\d{4})-(\d{2})-(\d{2})$/)
  if (soloFecha) return `${soloFecha[3]}/${soloFecha[2]}/${soloFecha[1]}`
  const d = new Date(s.includes('T') || s.includes('Z') ? s : s.replace(' ', 'T'))
  if (isNaN(d)) return s
  const fecha = d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
  if (!conHora) return fecha
  return `${fecha} ${d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`
}

/** Importe en euros: 1.234,50 € */
export function euros(v) {
  return EURO.format(Number(v) || 0)
}

/** Número con 2 decimales en formato español: 1.234,50 */
export function decimal2(v) {
  return DECIMAL2.format(Number(v) || 0)
}

/** Porcentaje con signo: +1,30 % / -1,28 % */
export function porcentaje(v) {
  const n = Number(v) || 0
  return `${n > 0 ? '+' : ''}${DECIMAL2.format(n)} %`
}

/** Fecha de hoy (local, no UTC) en formato YYYY-MM-DD para inputs type=date. */
export function hoyISO() {
  const h = new Date()
  return `${h.getFullYear()}-${String(h.getMonth() + 1).padStart(2, '0')}-${String(h.getDate()).padStart(2, '0')}`
}

/** Texto en minúsculas y sin tildes, para búsquedas que no distingan "sótano" de "sotano". */
export function normalizarBusqueda(v) {
  return String(v ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim()
}
