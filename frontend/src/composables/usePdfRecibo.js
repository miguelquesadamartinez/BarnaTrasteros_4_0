import { jsPDF } from 'jspdf'

const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const TIPOS_GASTO = { agua: 'Agua', luz: 'Luz', comunidad: 'Comunidad', mantenimiento: 'Mantenimiento', otro: 'Otro' }

function fmt(v) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v || 0)
}
function fmtDate(v) {
  return v ? v.split('T')[0] : '—'
}

async function loadLogo() {
  try {
    const backendBase = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
    const response = await fetch(`${backendBase}/logo`)
    if (response.ok) {
      const blob = await response.blob()
      return await new Promise((resolve) => {
        const reader = new FileReader()
        reader.onload = () => resolve(reader.result)
        reader.onerror = () => resolve(null)
        reader.readAsDataURL(blob)
      })
    }
  } catch (e) {
    console.warn('No se pudo cargar el logo:', e)
  }
  return null
}

function drawHeader(doc, logoData) {
  const hoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
  doc.setFillColor(252, 193, 5)
  doc.rect(0, 0, 210, 5, 'F')
  doc.setFillColor(248, 248, 248)
  doc.rect(0, 5, 210, 46, 'F')
  if (logoData) {
    doc.addImage(logoData, 'JPEG', 12, 8, 35, 14)
  }
  doc.setTextColor(0, 0, 0)
  doc.setFontSize(8)
  doc.setFont('helvetica', 'bold')
  doc.text('C/ Velia, 81 - 08016 - Barcelona', 12, 26)
  doc.text('Miguel Quesada Cantos', 12, 32)
  doc.text('DNI 36945618M', 12, 36)
  doc.text('Telf: 696 412 959 - 93 352 2003', 12, 42)
  doc.text('www.barnatrasteros.com', 12, 45)
  doc.text('info@barnatrasteros.com', 12, 48)
  doc.setTextColor(160, 160, 160)
  doc.setFontSize(8)
  doc.setFont('helvetica', 'normal')
  doc.text('BarnaTrasteros — Documento generado automáticamente', 105, 284, { align: 'center' })
  doc.text(`Generado el ${hoy}`, 105, 289, { align: 'center' })
}

/**
 * Genera un recibo en formato tabla (igual que facturas, sin IVA).
 * @param {string}   titulo       - Título del documento (ej: 'RECIBO DE PAGO')
 * @param {string}   numDoc       - Número/referencia del documento
 * @param {string}   hoy          - Fecha de emisión
 * @param {string[]} infoRef      - Líneas de info de referencia (izquierda)
 * @param {Array[]}  conceptoRows - Array de [concepto, detalle, importe]
 * @param {number}   total        - Total a mostrar en la caja final
 */
async function buildPdf(titulo, numDoc, hoy, infoRef, conceptoRows, total) {
  const logoData = await loadLogo()
  const doc = new jsPDF()

  drawHeader(doc, logoData)

  // Título y número a la derecha
  doc.setTextColor(0, 0, 0)
  doc.setFontSize(14)
  doc.setFont('helvetica', 'bold')
  doc.text(titulo, 195, 15, { align: 'right' })
  doc.setFontSize(10)
  doc.text(numDoc, 195, 23, { align: 'right' })
  doc.setFontSize(9)
  doc.setFont('helvetica', 'normal')
  doc.text(`Emitido: ${hoy}`, 195, 30, { align: 'right' })

  // Sección de referencia / cliente
  let y = 55
  doc.setTextColor(120, 120, 120)
  doc.setFontSize(8)
  doc.setFont('helvetica', 'bold')
  doc.text('Referencia:', 12, y + 2)
  doc.setDrawColor(220, 220, 220)
  doc.rect(12, y + 4, 85, 0.5, 'F')
  doc.setFont('helvetica', 'normal')
  doc.setTextColor(30, 30, 30)
  doc.setFontSize(9)
  infoRef.forEach((line, i) => {
    if (line) doc.text(line, 12, y + 10 + i * 6)
  })

  // Cabecera tabla conceptos
  y = 90
  doc.setFillColor(252, 193, 5)
  doc.rect(12, y, 185, 9, 'F')
  doc.setTextColor(0, 0, 0)
  doc.setFontSize(9)
  doc.setFont('helvetica', 'bold')
  doc.text('Concepto', 18, y + 6.5)
  doc.text('Detalle', 100, y + 6.5)
  doc.text('Importe', 185, y + 6.5, { align: 'right' })

  // Filas de conceptos
  y += 9
  const ROW = 9
  conceptoRows.forEach(([concepto, detalle, importe], i) => {
    doc.setFillColor(i % 2 === 0 ? 248 : 255, i % 2 === 0 ? 248 : 255, i % 2 === 0 ? 248 : 255)
    doc.rect(12, y, 185, ROW, 'F')
    doc.setDrawColor(230, 230, 230)
    doc.line(12, y + ROW, 197, y + ROW)
    doc.setTextColor(30, 30, 30)
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(9)
    doc.text(String(concepto ?? '—'), 18, y + 6)
    doc.text(String(detalle ?? '—'), 100, y + 6)
    doc.setFont('helvetica', 'bold')
    doc.text(fmt(importe), 185, y + 6, { align: 'right' })
    y += ROW
  })

  // Caja total
  y += 6
  doc.setDrawColor(252, 193, 5)
  doc.setLineWidth(0.5)
  doc.line(120, y, 197, y)
  y += 5
  doc.setFillColor(252, 193, 5)
  doc.rect(118, y - 6, 79, 10, 'F')
  doc.setTextColor(0, 0, 0)
  doc.setFont('helvetica', 'bold')
  doc.setFontSize(10)
  doc.text('TOTAL:', 130, y)
  doc.text(fmt(total), 185, y, { align: 'right' })

  return doc
}

export function usePdfRecibo() {
  /**
   * Genera y descarga el recibo de un pago de alquiler (trastero/piso).
   * @param {Object} pago       - Objeto PagoAlquiler (con cliente, mes, anyo, etc.)
   * @param {Object} detalle    - Objeto DetallePagoAlquiler (importe, fecha_pago, notas)
   */
  async function generarReciboPago(pago, detalle) {
    const hoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
    const numDoc = `Nº ${detalle.id}`
    const infoRef = [
      pago.cliente ? `${pago.cliente.nombre} ${pago.cliente.apellido}` : null,
      `${pago.tipo === 'piso' ? 'Piso' : 'Trastero'} #${pago.referencia_id}`,
      `Período: ${MESES[pago.mes]} ${pago.anyo}`,
      `Estado: ${pago.estado}`,
    ]
    const conceptoRows = [
      ['Pago de alquiler', fmtDate(detalle.fecha_pago), detalle.importe],
    ]
    if (detalle.notas) conceptoRows.push(['Notas', detalle.notas, ''])
    const doc = await buildPdf('RECIBO DE PAGO', numDoc, hoy, infoRef, conceptoRows, detalle.importe)
    doc.save(`recibo_alquiler_${pago.tipo}_ref${pago.referencia_id}_${pago.mes}-${pago.anyo}_id${detalle.id}.pdf`)
  }

  async function generarReciboGasto(gasto, detalle) {
    const hoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
    const numDoc = `Nº ${detalle.id}`
    const infoRef = [
      `Tipo: ${TIPOS_GASTO[gasto.tipo] || gasto.tipo}`,
      gasto.descripcion,
      gasto.referencia_tipo !== 'general' ? `${gasto.referencia_tipo} #${gasto.referencia_id}` : 'General',
      `Estado: ${gasto.estado}`,
    ]
    const conceptoRows = [
      [gasto.descripcion, fmtDate(detalle.fecha_pago), detalle.importe],
    ]
    if (detalle.notas) conceptoRows.push(['Notas', detalle.notas, ''])
    const doc = await buildPdf('RECIBO DE GASTO', numDoc, hoy, infoRef, conceptoRows, detalle.importe)
    doc.save(`recibo_gasto_${gasto.id}_pago_${detalle.id}.pdf`)
  }

  async function generarReciboPagoTotal(pago) {
    const hoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
    const numDoc = `${pago.tipo === 'piso' ? 'Piso' : 'Trastero'} #${pago.referencia_id}`
    const infoRef = [
      pago.cliente ? `${pago.cliente.nombre} ${pago.cliente.apellido}` : null,
      `Período: ${MESES[pago.mes]} ${pago.anyo}`,
      `Estado: ${pago.estado}`,
    ]
    const pendiente = Math.max(0, +pago.importe_total - +pago.pagado)
    const conceptoRows = [
      ['Alquiler mensual', `${MESES[pago.mes]} ${pago.anyo}`, pago.importe_total],
      ['Total abonado',    hoy,                                 pago.pagado],
      ['Pendiente',        '',                                  pendiente],
    ]
    if (pago.notas) conceptoRows.push(['Notas', pago.notas, ''])
    const doc = await buildPdf('RECIBO', numDoc, hoy, infoRef, conceptoRows, pago.importe_total)
    doc.save(`recibo_alquiler_${pago.tipo}_ref${pago.referencia_id}_${pago.mes}-${pago.anyo}.pdf`)
  }

  async function generarReciboGastoTotal(gasto) {
    const hoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
    const numDoc = `Gasto #${gasto.id}`
    const infoRef = [
      `Tipo: ${TIPOS_GASTO[gasto.tipo] || gasto.tipo}`,
      gasto.descripcion,
      gasto.referencia_tipo !== 'general' ? `${gasto.referencia_tipo} #${gasto.referencia_id}` : 'General',
      `Estado: ${gasto.estado}`,
    ]
    const pendiente = Math.max(0, +gasto.importe_total - +gasto.pagado)
    const conceptoRows = [
      [gasto.descripcion,   `Emisión: ${fmtDate(gasto.fecha_emision)}`,      gasto.importe_total],
      ['Total abonado',     `Vence: ${fmtDate(gasto.fecha_vencimiento)}`,     gasto.pagado],
      ['Pendiente',         '',                                                pendiente],
    ]
    if (gasto.notas) conceptoRows.push(['Notas', gasto.notas, ''])
    const doc = await buildPdf('RECIBO', numDoc, hoy, infoRef, conceptoRows, gasto.importe_total)
    doc.save(`recibo_gasto_${gasto.id}_${gasto.tipo}.pdf`)
  }

  /**
   * Genera una factura formal para un cliente con todos sus cargos del mes.
   * @param {Object} factura - { cliente, pagos, importe_total, total_pagado }
   * @param {number} mes
   * @param {number} anyo
   */

  // eMiKi

  async function generarFacturaCliente(factura, mes, anyo) {
    const { cliente, pagos, importe_total, total_pagado } = factura
    const hoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
    const numFactura = `${anyo} - ${String(mes).padStart(2,'0')}-${String(cliente.id).padStart(4,'0')}`

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
    doc.setFillColor(252, 193, 5)
    doc.rect(0, 0, 210, 5, 'F')

    doc.setFillColor(248, 248, 248)
    doc.rect(0, 5, 210, 46, 'F')
    
    // Texto de empresa siempre visible
    // Logo encima del texto si se cargó
    if (logoData) {
      doc.addImage(logoData, 'JPEG', 12,8, 35, 14)
    }
    doc.setTextColor(0, 0, 0)
    doc.setFontSize(8)
    doc.setFont('helvetica', 'bold')
    doc.text('C/ Velia, 81 - 08016 - Barcelona', 12, 26)
    doc.text('Miguel Quesada Cantos', 12, 32)
    doc.text('DNI 36945618M', 12, 36)

    doc.text('Telf: 696 412 959 - 93 352 2003', 12, 42)
    doc.text('www.barnatrasteros.com', 12, 45)
    doc.text('info@barnatrasteros.com', 12, 48)
    

    // Número factura y fecha
    doc.setTextColor(0, 0, 0)
    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.text(`FACTURA`, 195, 16, { align: 'right' })
    doc.setFontSize(9)
    doc.setFont('helvetica', 'normal')
    doc.text(`Fecha:`, 195, 24, { align: 'right' })
    doc.text(`${hoy}`, 195, 30, { align: 'right' })

    doc.text(`Nº Factura ${numFactura}`, 195, 38, { align: 'right' })

    // Datos cliente
    let y = 55
    //doc.setFillColor(248, 248, 248)
    //doc.rect(15, y - 4, 85, 48, 'F')
    //doc.setDrawColor(220, 220, 220)
    //doc.rect(15, y - 4, 85, 48, 'S')
    doc.setTextColor(120, 120, 120)
    doc.setFontSize(8)
    doc.setFont('helvetica', 'bold')
    doc.text('Facturado a:', 12, y + 2)
    doc.rect(12, y + 4, 85, 0.5, 'S')
    doc.rect(12, y + 4, 85, 0.5, 'F')
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(30, 30, 30)
    doc.setFontSize(10)
    doc.text(`${cliente.nombre} ${cliente.apellido}`, 12, y + 9)
    doc.setFontSize(9)
    doc.setTextColor(80, 80, 80)
    doc.text(`Número identificación fiscal: ${cliente.dni}`, 12, y + 15)
    if (cliente.direccion) doc.text(cliente.direccion, 12, y + 20)
    if (cliente.ciudad) doc.text(`${cliente.codigo_postal || ''} ${cliente.ciudad}`.trim(), 12, y + (cliente.direccion ? 25 : 20))

    // Tabla de conceptos
    y = 90
    doc.setFillColor(252, 193, 5)
    doc.rect(12, y, 185, 9, 'F')
    doc.setTextColor(255, 255, 255)
    doc.setFontSize(9)
    doc.setFont('helvetica', 'bold')
    doc.text('Concepto', 18, y + 6.5)
    doc.text('Período', 100, y + 6.5)
    doc.text('Importe', 185, y + 6.5, { align: 'right' })

    y += 9
    const ROW = 9
    pagos.forEach((p, i) => {
      const label = p.tipo === 'piso' ? `Arrendamiento Piso ${p.numero ?? p.referencia_id}` : `Arrendamiento Trastero ${p.numero ?? p.referencia_id}`
      doc.setFillColor(i % 2 === 0 ? 248 : 255, i % 2 === 0 ? 248 : 255, i % 2 === 0 ? 248 : 255)
      doc.rect(12, y, 185, ROW, 'F')
      doc.setDrawColor(230, 230, 230)
      doc.line(12, y + ROW, 195, y + ROW)
      doc.setTextColor(30, 30, 30)
      doc.setFont('helvetica', 'normal')
      doc.setFontSize(9)
      doc.text(label, 15, y + 6)
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
    doc.setDrawColor(252, 193, 5)
    doc.setLineWidth(0.5)
    doc.line(120, y, 195, y)
    y += 5

    const totalesRows = [
      { lbl: 'Base imponible:',  val: fmt(baseImponible), bold: false },
      { lbl: 'IVA (21%):',       val: fmt(ivaImporte),    bold: false },
      { lbl: 'TOTAL FACTURA:',   val: fmt(totalFactura),  bold: true,  highlight: true },
    ]

    totalesRows.forEach(({ lbl, val, bold, highlight }) => {
      if (highlight) {
        doc.setFillColor(252, 193, 5)
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

    doc.save(`Factura ${numFactura}_${cliente.apellido.replace(/\s+/g,'_')}.pdf`)
  }

  return { generarReciboPago, generarReciboGasto, generarReciboPagoTotal, generarReciboGastoTotal, generarFacturaCliente }
}
