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

  /**
   * Genera una factura formal para un cliente con todos sus cargos del mes.
   * @param {Object} factura - { cliente, pagos, importe_total, total_pagado }
   * @param {number} mes
   * @param {number} anyo
   */
  async function generarFacturaCliente(factura, mes, anyo) {
    const { cliente, pagos, importe_total, total_pagado } = factura
    const hoy = new Date().toLocaleDateString('es-ES')
    const numFactura = `Factura ${anyo} - ${String(mes).padStart(2,'0')}-${String(cliente.id).padStart(4,'0')}`

    // Cargar logo JPG desde la API del backend (con CORS)
    let logoData = null
    try {
      const backendBase = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
      const response = await fetch(`${backendBase}/logo`)
      if (response.ok) {
        const blob = await response.blob()
        logoData = await new Promise((resolve) => {
          const reader = new FileReader()
          reader.onload = () => resolve(reader.result)
          reader.onerror = () => resolve(null)
          reader.readAsDataURL(blob)
        })
      }
    } catch (e) {
      console.warn('No se pudo cargar el logo:', e)
    }

    const doc = new jsPDF()

    // Cabecera roja
    doc.setFillColor(255, 255, 255)
    doc.rect(0, 0, 210, 5, 'F')

    doc.setFillColor(192, 57, 43)
    doc.rect(0, 5, 210, 36, 'F')
    
    // Texto de empresa siempre visible
    doc.setTextColor(255, 255, 255)
    doc.setFontSize(16)
    doc.setFont('helvetica', 'bold')
    doc.text('BARNATRASTEROS', 15, 14)
    doc.setFontSize(8)
    doc.setFont('helvetica', 'normal')
    doc.text('Sistema de Gestión de Alquileres', 15, 24)
    // Logo encima del texto si se cargó
    if (logoData) {
      doc.addImage(logoData, 'JPEG', 12, 10, 72, 26)
    }

    // Número factura y fecha
    doc.setTextColor(255, 255, 255)
    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.text(`FACTURA ${numFactura}`, 195, 20, { align: 'right' })
    doc.setFontSize(9)
    doc.setFont('helvetica', 'normal')
    doc.text(`Período: ${MESES[mes]} ${anyo}`, 195, 28, { align: 'right' })
    doc.text(`Emitida: ${hoy}`, 195, 34, { align: 'right' })

    // Datos cliente
    let y = 50
    doc.setFillColor(248, 248, 248)
    doc.rect(15, y - 4, 85, 38, 'F')
    doc.setDrawColor(220, 220, 220)
    doc.rect(15, y - 4, 85, 38, 'S')
    doc.setTextColor(120, 120, 120)
    doc.setFontSize(8)
    doc.setFont('helvetica', 'bold')
    doc.text('DATOS DEL CLIENTE', 18, y + 2)
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(30, 30, 30)
    doc.setFontSize(10)
    doc.text(`${cliente.nombre} ${cliente.apellido}`, 18, y + 9)
    doc.setFontSize(9)
    doc.setTextColor(80, 80, 80)
    doc.text(`DNI: ${cliente.dni}`, 18, y + 15)
    if (cliente.telefono) doc.text(`Tel: ${cliente.telefono}`, 18, y + 20)
    if (cliente.direccion) doc.text(cliente.direccion, 18, y + 25)
    if (cliente.ciudad) doc.text(`${cliente.codigo_postal || ''} ${cliente.ciudad}`.trim(), 18, y + (cliente.direccion ? 30 : 25))

    // Tabla de conceptos
    y = 90
    doc.setFillColor(192, 57, 43)
    doc.rect(15, y, 180, 9, 'F')
    doc.setTextColor(255, 255, 255)
    doc.setFontSize(9)
    doc.setFont('helvetica', 'bold')
    doc.text('Concepto', 18, y + 6.5)
    doc.text('Período', 100, y + 6.5)
    doc.text('Importe', 185, y + 6.5, { align: 'right' })

    y += 9
    const ROW = 9
    pagos.forEach((p, i) => {
      const label = p.tipo === 'piso' ? `Alquiler Piso #${p.referencia_id}` : `Alquiler Trastero #${p.referencia_id}`
      doc.setFillColor(i % 2 === 0 ? 248 : 255, i % 2 === 0 ? 248 : 255, i % 2 === 0 ? 248 : 255)
      doc.rect(15, y, 180, ROW, 'F')
      doc.setDrawColor(230, 230, 230)
      doc.line(15, y + ROW, 195, y + ROW)
      doc.setTextColor(30, 30, 30)
      doc.setFont('helvetica', 'normal')
      doc.setFontSize(9)
      doc.text(label, 18, y + 6)
      doc.text(`${MESES[mes]} ${anyo}`, 100, y + 6)
      doc.setFont('helvetica', 'bold')
      doc.text(fmt(p.importe_total), 185, y + 6, { align: 'right' })
      y += ROW
    })

    // Totales con desglose IVA (IVA se resta del total)
    const totalFactura  = +importe_total
    const baseImponible = totalFactura / 1.21
    const ivaImporte    = totalFactura - baseImponible
    const totalPagado   = +total_pagado
    const basePagada    = totalPagado / 1.21
    const pendiente     = Math.max(0, totalFactura - totalPagado)

    y += 6
    doc.setDrawColor(192, 57, 43)
    doc.setLineWidth(0.5)
    doc.line(120, y, 195, y)
    y += 5

    const totalesRows = [
      { lbl: 'Base imponible:',  val: fmt(baseImponible), bold: false },
      { lbl: 'IVA (21%):',       val: fmt(ivaImporte),    bold: false },
      { lbl: 'TOTAL FACTURA:',   val: fmt(totalFactura),  bold: true,  highlight: true },
      { lbl: 'Total pagado:',    val: fmt(totalPagado),   bold: false },
      { lbl: 'Pendiente:',       val: fmt(pendiente),     bold: pendiente > 0 },
    ]

    totalesRows.forEach(({ lbl, val, bold, highlight }) => {
      if (highlight) {
        doc.setFillColor(192, 57, 43)
        doc.rect(118, y - 6, 79, 10, 'F')
        doc.setTextColor(255, 255, 255)
      } else {
        doc.setTextColor(80, 80, 80)
      }
      doc.setFont('helvetica', bold ? 'bold' : 'normal')
      doc.setFontSize(bold ? 10 : 9)
      doc.text(lbl, 130, y)
      doc.setFont('helvetica', bold ? 'bold' : 'normal')
      doc.text(val, 185, y, { align: 'right' })
      y += 8
    })

    // Pie
    doc.setTextColor(160, 160, 160)
    doc.setFontSize(8)
    doc.setFont('helvetica', 'normal')
    doc.text('BarnaTrasteros — Documento generado automáticamente', 105, 284, { align: 'center' })
    doc.text(`Generado el ${hoy}`, 105, 289, { align: 'center' })

    doc.save(`${numFactura}_${cliente.apellido.replace(/\s+/g,'_')}.pdf`)
  }

  return { generarReciboPago, generarReciboGasto, generarReciboPagoTotal, generarReciboGastoTotal, generarFacturaCliente }
}
