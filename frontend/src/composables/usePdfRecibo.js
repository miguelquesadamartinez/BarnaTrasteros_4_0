import { jsPDF } from 'jspdf'

const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const TIPOS_GASTO = { agua: 'Agua', luz: 'Luz', comunidad: 'Comunidad', mantenimiento: 'Mantenimiento', otro: 'Otro' }

function fmt(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}
function fmtDate(v) {
  return v ? v.split('T')[0] : '—'
}

function buildPdf(titulo, rows, importeDetalle) {
  const doc = new jsPDF()

  // ── Cabecera azul ──────────────────────────────────────────
  doc.setFillColor(25, 55, 110)
  doc.rect(0, 0, 210, 32, 'F')
  doc.setTextColor(255, 255, 255)
  doc.setFontSize(22)
  doc.setFont('helvetica', 'bold')
  doc.text('BARNATRASTEROS', 15, 19)
  doc.setFontSize(10)
  doc.setFont('helvetica', 'normal')
  doc.text('Sistema de Gestión de Alquileres', 15, 27)

  // ── Título del recibo ──────────────────────────────────────
  doc.setTextColor(25, 55, 110)
  doc.setFontSize(15)
  doc.setFont('helvetica', 'bold')
  doc.text(titulo, 105, 47, { align: 'center' })
  doc.setDrawColor(25, 55, 110)
  doc.setLineWidth(0.5)
  doc.line(15, 51, 195, 51)

  // ── Tabla de datos ─────────────────────────────────────────
  let y = 63
  const ROW_H = 9

  rows.forEach(([label, value], i) => {
    const par = i % 2 === 0
    doc.setFillColor(par ? 243 : 255, par ? 246 : 255, par ? 253 : 255)
    doc.rect(15, y - 6.5, 180, ROW_H, 'F')
    doc.setFont('helvetica', 'bold')
    doc.setTextColor(80, 80, 80)
    doc.setFontSize(10)
    doc.text(label, 18, y)
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(20, 20, 20)
    doc.text(String(value ?? '—'), 95, y)
    y += ROW_H
  })

  // ── Caja de importe total ──────────────────────────────────
  y += 8
  doc.setFillColor(25, 55, 110)
  doc.roundedRect(120, y, 75, 16, 3, 3, 'F')
  doc.setTextColor(255, 255, 255)
  doc.setFontSize(12)
  doc.setFont('helvetica', 'bold')
  doc.text(`IMPORTE: ${fmt(importeDetalle)}`, 157.5, y + 10, { align: 'center' })

  // ── Pie de página ──────────────────────────────────────────
  doc.setTextColor(160, 160, 160)
  doc.setFontSize(8)
  doc.setFont('helvetica', 'normal')
  doc.text('BarnaTrasteros — Documento generado automáticamente', 105, 284, { align: 'center' })
  doc.text(`Generado el ${new Date().toLocaleDateString('es-ES')}`, 105, 289, { align: 'center' })

  return doc
}

export function usePdfRecibo() {
  /**
   * Genera y descarga el recibo de un pago de alquiler (trastero/piso).
   * @param {Object} pago       - Objeto PagoAlquiler (con cliente, mes, anyo, etc.)
   * @param {Object} detalle    - Objeto DetallePagoAlquiler (importe, fecha_pago, notas)
   */
  function generarReciboPago(pago, detalle) {
    const rows = [
      ['Nº Recibo',          `#${detalle.id}`],
      ['Tipo de propiedad',  pago.tipo === 'piso' ? '🏠 Piso' : '📦 Trastero'],
      ['Referencia',         `${pago.tipo} #${pago.referencia_id}`],
      ['Cliente',            pago.cliente ? `${pago.cliente.nombre} ${pago.cliente.apellido}` : '—'],
      ['Período',            `${MESES[pago.mes]} ${pago.anyo}`],
      ['Fecha de pago',      fmtDate(detalle.fecha_pago)],
      ['Importe pagado',     fmt(detalle.importe)],
      ['Importe total mes',  fmt(pago.importe_total)],
      ['Total abonado',      fmt(pago.pagado)],
      ['Estado',             pago.estado],
    ]
    if (detalle.notas) rows.push(['Notas', detalle.notas])

    const doc = buildPdf('RECIBO DE PAGO DE ALQUILER', rows, detalle.importe)
    doc.save(`recibo_alquiler_${pago.tipo}_ref${pago.referencia_id}_${pago.mes}-${pago.anyo}_id${detalle.id}.pdf`)
  }

  /**
   * Genera y descarga el recibo de un pago de gasto.
   * @param {Object} gasto   - Objeto Gasto completo
   * @param {Object} detalle - Objeto DetalleGasto (importe, fecha_pago, notas)
   */
  function generarReciboGasto(gasto, detalle) {
    const rows = [
      ['Nº Recibo',       `#${detalle.id}`],
      ['Tipo de gasto',   TIPOS_GASTO[gasto.tipo] || gasto.tipo],
      ['Descripción',     gasto.descripcion],
      ['Referencia',      gasto.referencia_tipo !== 'general'
                            ? `${gasto.referencia_tipo} #${gasto.referencia_id}`
                            : 'General'],
      ['Fecha emisión',   fmtDate(gasto.fecha_emision)],
      ['Fecha de pago',   fmtDate(detalle.fecha_pago)],
      ['Importe pagado',  fmt(detalle.importe)],
      ['Total gasto',     fmt(gasto.importe_total)],
      ['Total abonado',   fmt(gasto.pagado)],
      ['Estado',          gasto.estado],
    ]
    if (detalle.notas) rows.push(['Notas', detalle.notas])

    const doc = buildPdf('RECIBO DE PAGO DE GASTO', rows, detalle.importe)
    doc.save(`recibo_gasto_${gasto.id}_pago_${detalle.id}.pdf`)
  }

  /**
   * Genera un recibo/factura del estado actual de un pago de alquiler
   * sin necesitar un detalle concreto. Sirve para cualquier estado.
   * @param {Object} pago - Objeto PagoAlquiler
   */
  function generarReciboPagoTotal(pago) {
    const hoy = new Date().toLocaleDateString('es-ES')
    const rows = [
      ['Tipo de propiedad',  pago.tipo === 'piso' ? '🏠 Piso' : '📦 Trastero'],
      ['Referencia',         `${pago.tipo} #${pago.referencia_id}`],
      ['Cliente',            pago.cliente ? `${pago.cliente.nombre} ${pago.cliente.apellido}` : '—'],
      ['Período',            `${MESES[pago.mes]} ${pago.anyo}`],
      ['Importe total',      fmt(pago.importe_total)],
      ['Total abonado',      fmt(pago.pagado)],
      ['Pendiente',          fmt(Math.max(0, +pago.importe_total - +pago.pagado))],
      ['Estado',             pago.estado],
      ['Fecha emisión',      hoy],
    ]
    if (pago.notas) rows.push(['Notas', pago.notas])

    const doc = buildPdf('RESUMEN DE ALQUILER', rows, pago.importe_total)
    doc.save(`resumen_alquiler_${pago.tipo}_ref${pago.referencia_id}_${pago.mes}-${pago.anyo}.pdf`)
  }

  /**
   * Genera un recibo/factura del estado actual de un gasto
   * sin necesitar un detalle concreto. Sirve para cualquier estado.
   * @param {Object} gasto - Objeto Gasto completo
   */
  function generarReciboGastoTotal(gasto) {
    const hoy = new Date().toLocaleDateString('es-ES')
    const rows = [
      ['Tipo de gasto',   TIPOS_GASTO[gasto.tipo] || gasto.tipo],
      ['Descripción',     gasto.descripcion],
      ['Referencia',      gasto.referencia_tipo !== 'general'
                            ? `${gasto.referencia_tipo} #${gasto.referencia_id}`
                            : 'General'],
      ['Fecha emisión',   fmtDate(gasto.fecha_emision)],
      ['Vencimiento',     fmtDate(gasto.fecha_vencimiento)],
      ['Importe total',   fmt(gasto.importe_total)],
      ['Total abonado',   fmt(gasto.pagado)],
      ['Pendiente',       fmt(Math.max(0, +gasto.importe_total - +gasto.pagado))],
      ['Estado',          gasto.estado],
      ['Fecha emisión doc', hoy],
    ]
    if (gasto.notas) rows.push(['Notas', gasto.notas])

    const doc = buildPdf('RESUMEN DE GASTO', rows, gasto.importe_total)
    doc.save(`resumen_gasto_${gasto.id}_${gasto.tipo}.pdf`)
  }

  return { generarReciboPago, generarReciboGasto, generarReciboPagoTotal, generarReciboGastoTotal }
}
