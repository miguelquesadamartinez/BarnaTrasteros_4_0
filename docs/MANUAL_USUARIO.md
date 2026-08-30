# Manual de Usuario — BarnaTrasteros

Guía visual de todas las pantallas y funciones de la aplicación. Uso interno, sin login: cualquiera con acceso a `localhost:5173` puede usar cualquier función.

## Índice

- [Panel de Control (Inicio)](#panel-de-control-inicio)
- [Trasteros](#trasteros)
- [Pisos](#pisos)
- [Clientes](#clientes)
- [Lista de Espera](#lista-de-espera)
- [Fianzas](#fianzas)
- [Pagos](#pagos)
- [Gastos](#gastos)
- [Facturas](#facturas)
- [Avisar Impagos](#avisar-impagos)
- [Relatorios](#relatorios)
- [Mantenimiento](#mantenimiento)
- [Búsqueda global](#búsqueda-global)

---

## Panel de Control (Inicio)

![Panel de Control](manual/images/00_inicio_panel_control.png)

Primera pantalla al entrar. De un vistazo:

- **Tarjetas superiores**: trasteros alquilados/libres, pisos alquilados/libres, total pendiente de cobro en alquileres, total pendiente en gastos.
- **Tabla "Pagos Pendientes"**: todos los alquileres (trastero o piso) con algo pendiente de cobro, ordenados por antigüedad. Se puede filtrar por texto (cliente), por tipo (trastero/piso) y cambiar cuántas filas se ven por página.
- **Acciones por fila**:
  - **Pagar** — abre el modal de registrar un cobro para ese mes.
  - **Ver** — abre el detalle de los pagos ya registrados de esa unidad/mes (recibos en PDF, reenviar por email).
  - Icono de **hoja** — imprime el recibo.
  - Icono de **campana** — envía al cliente un email recordándole que tiene un pago pendiente.

---

## Trasteros

![Listado de trasteros](manual/images/01_trasteros_lista.png)

Listado con todos los trasteros: número, piso, tamaño, precio/mes, cliente asignado y estado (**ALQUILADO** / **LIBRE**). Buscador por número, piso o tamaño.

**Acciones por fila:**
- **Editar** — abre el formulario de edición (mismos campos que "Nuevo Trastero", más los de abajo).
- **Dar de baja** (solo si está alquilado) — libera la unidad.
- **Eliminar** — borra el trastero (bloqueado si tiene pagos o fianzas asociados).

### Nuevo Trastero / Editar Trastero

![Formulario nuevo piso](manual/images/02b_pisos_nuevo_modal.png)
*(el formulario de Trastero es idéntico al de Piso, solo cambia "Piso" por "Tamaño")*

Campos: número, piso/planta, tamaño (del catálogo de Mantenimiento), precio mensual, cliente asignado (buscador), notas.

Al **editar** un trastero ya alquilado aparecen además:

![Editar piso con cliente asignado](manual/images/02c_pisos_editar_modal.png)
*(igual en Trastero)*

- **Fecha inicio alquiler** y **Fecha de vencimiento** — la fecha de vencimiento marca a partir de cuándo se considera atrasado el pago si no se ha cobrado; por defecto es un mes después del inicio, pero se puede ajustar a mano.

### Asignar un trastero libre a un cliente nuevo

Al asignar un cliente a una unidad libre (desde aquí o desde la ficha del Cliente) se pide el **importe a cobrar este mes**, con el prorrateo ya calculado según los días que quedan del mes desde el inicio del alquiler, y se informa del precio mensual completo como referencia. Es editable antes de guardar.

### Dar de baja

![Dar de baja con importe prorrateado](manual/images/02d_pisos_dar_baja_modal.png)
*(igual en Trastero)*

Al dar de baja a un cliente se pregunta el **importe a cobrar este mes** (el mes en que se va), con el mismo prorrateo por defecto y el precio mensual completo como referencia. Confirmar libera la unidad para poder asignarla a otro cliente.

---

## Pisos

![Listado de pisos](manual/images/02_pisos_lista.png)

Funciona exactamente igual que Trasteros (ver arriba): listado, alta, edición, asignación de cliente con importe prorrateado, dar de baja, eliminar.

---

## Clientes

![Listado de clientes](manual/images/03_clientes_lista.png)

Listado con nombre, DNI, teléfono, trastero(s)/piso asignados, si tiene foto de DNI subida. Buscador por nombre, apellido o DNI.

**Acciones por fila:**
- **Editar** — abre la ficha completa del cliente.
- **Archivar** — da de baja automáticamente todas sus unidades (avisando antes de lo pendiente) y lo oculta del listado sin borrar su historial. Aparece luego en Mantenimiento → Clientes Archivados.
- **Eliminar** — solo si el cliente no tiene pagos ni fianzas registradas; si los tiene, hay que usar Archivar en su lugar.

### Nuevo Cliente

![Formulario nuevo cliente](manual/images/03b_clientes_nuevo_modal.png)

- Datos básicos: nombre, apellido, DNI, teléfono, email, dirección, código postal, ciudad.
- **Necesita factura mensual** — si se marca, este cliente se incluye en la generación automática de facturas.
- **Foto del DNI** — JPG, PNG o PDF, máx. 5MB.
- **Propiedades asociadas** (opcional, se puede completar después): buscador para añadir trastero(s), buscador para asignar un piso.
- **Fianzas**: solo se pueden añadir una vez asignada al menos una unidad — importe y fecha.

Al guardar con alguna unidad asignada se genera automáticamente el contrato de alquiler en PDF y se abre en una pestaña nueva.

### Ficha de Cliente (Editar)

![Ficha de cliente con unidad asignada](manual/images/03c_clientes_editar_modal.png)

Además de los datos básicos:

- **Generar contrato** — crea (o regenera) el PDF del contrato con las unidades actuales.
- **Pago pendiente** — si hay algo pendiente, aparece el total y el botón **Avisar de pago pendiente** (envía un email al cliente).
- **Trasteros asignados / Piso asignado** — se pueden añadir o quitar desde aquí mismo.
- **Fecha de vencimiento del alquiler** — editable por unidad, con su propio botón Guardar.
- **Fianzas** — añadir importe y fecha; quitar una fianza la marca como devuelta (no la borra, pasa a Fianzas Devueltas).

### Asignar una unidad nueva desde la ficha del cliente

![Importe prorrateado al asignar unidad](manual/images/03d_clientes_asignar_unidad_prorateo.png)

Al añadir un trastero o piso libre aparece **"Importe a cobrar este mes"**, prorrateado por defecto según los días de alquiler de este mes, junto con el precio mensual completo como referencia — igual que al asignar desde Trasteros/Pisos directamente.

---

## Lista de Espera

![Lista de espera](manual/images/04_lista_espera.png)

Clientes interesados cuando no hay unidad libre del tamaño que buscan.

![Añadir a lista de espera](manual/images/04b_lista_espera_nuevo_modal.png)

- **+ Añadir**: nombre, teléfono, tamaño buscado, fecha.
- **Eliminar**: quita el registro manualmente.
- Los registros de más de 2 meses se borran solos cada noche.

---

## Fianzas

### Fianzas Activas

![Fianzas activas](manual/images/05_fianzas_activas.png)

Todas las fianzas cobradas y aún no devueltas, con el cliente y la unidad a la que están ligadas. Se gestionan (añadir/quitar) desde la ficha del cliente, no desde aquí.

### Fianzas Devueltas

![Fianzas devueltas](manual/images/06_fianzas_devueltas.png)

Historial de fianzas ya devueltas — quedan aquí como registro, no se pueden reactivar.

---

## Pagos

![Gestión de pagos](manual/images/07_pagos_lista.png)

Todos los recibos de alquiler (trastero y piso), mes a mes. Filtros por cliente, tipo, estado, año y mes.

**Acciones por fila:**
- **Pagar** (moneda) — registrar un cobro.
- **Ver** — ver los pagos ya registrados de ese recibo.
- Icono **hoja** — imprimir recibo.
- Icono **campana** — enviar email de recordatorio (solo si queda algo pendiente).
- **Eliminar** (solo pendientes sin nada cobrado).

### Registrar Pago

![Registrar pago](manual/images/07b_pagos_registrar_modal.png)

Importe a pagar (por defecto el pendiente total del cliente — el cobro se reparte automáticamente entre los meses más antiguos con deuda, sin poder superar el total pendiente), fecha de pago y notas opcionales.

### Detalle de Pagos

![Detalle de pagos](manual/images/07c_pagos_detalle_modal.png)

Lista de los cobros ya registrados para ese recibo: fecha, importe, botón para el PDF del recibo y para reenviarlo por email, y eliminar un cobro concreto si se registró por error.

---

## Gastos

![Gestión de gastos](manual/images/08_gastos_lista.png)

Gastos del negocio (agua, luz, comunidad, mantenimiento, otros), opcionalmente ligados a un trastero o piso concreto. Filtros por tipo, estado, mes y año.

**Acciones por fila:** registrar pago, ver pagos registrados, imprimir/enviar recibo general, ver imágenes adjuntas, editar, eliminar.

### Nuevo Gasto

![Nuevo gasto](manual/images/08b_gastos_nuevo_modal.png)

Tipo, descripción, referencia (trastero/piso si aplica), importe total, fecha de vencimiento, e imágenes adjuntas (facturas escaneadas, fotos).

### Registrar Pago del Gasto

![Registrar pago de gasto](manual/images/08c_gastos_registrar_pago_modal.png)

Igual que en Pagos: admite pagos parciales — el gasto queda en estado **PARCIAL** hasta cubrir el total, luego pasa a **PAGADO**.

---

## Facturas

![Facturas del mes](manual/images/09_facturas.png)

Genera y envía las facturas mensuales a los clientes marcados como "Necesita factura mensual". Desde aquí se descarga el PDF o se envía por email directamente al cliente.

---

## Avisar Impagos

![Avisar impagos](manual/images/10_avisar_impagos.png)

Envío manual de avisos de impago a todos los clientes con algo pendiente. Es la versión manual del job automático que corre cada día a las 09:00 (ese respeta un margen de 5 días desde el vencimiento; el botón manual no aplica ese margen).

---

## Relatorios

![Relatorios — pestaña Trasteros](manual/images/11_relatorios.png)

Cuatro pestañas (**Trasteros**, **Pisos**, **Pagos**, **Gastos**) con el estado completo de cada área: totales, alquilado/libre, cliente y fecha de inicio por unidad. Botón **Recargar** para refrescar los datos sin salir de la página.

---

## Mantenimiento

### Tamaños de Trasteros

![Tamaños de trastero](manual/images/12_mantenimiento_tamanyos.png)

Catálogo de tamaños (Pequeño, Mediano, Grande, Extra...) que se usa como lista desplegable al crear/editar un trastero. Renombrar un tamaño actualiza en cascada todos los trasteros que lo usan; no se puede borrar un tamaño que esté en uso.

### Clientes Archivados

![Clientes archivados](manual/images/13_mantenimiento_clientes_archivados.png)

Clientes que se archivaron desde el listado de Clientes (ver arriba). Conservan su historial de pagos y fianzas pero no aparecen en el listado normal ni en los buscadores de asignación.

### Generar Pagos

![Generar pagos](manual/images/14_mantenimiento_generar_pagos.png)

Versión manual del job automático del día 1 de cada mes: crea el recibo pendiente de cada trastero/piso alquilado para el mes y año indicados (por si hace falta regenerar algo o correrlo fuera de fecha).

### Revisión de Precio

![Revisión de precio](manual/images/15_mantenimiento_revision_precio.png)

Cambia el precio mensual de una unidad (o de varias a la vez) y, opcionalmente, avisa por email al cliente afectado del nuevo precio. Cada cambio queda registrado en el Historial de Precios.

### Historial de Precios

![Historial de precios](manual/images/16_mantenimiento_historial_precios.png)

Registro de todos los cambios de precio hechos desde "Revisión de Precio": unidad, precio anterior, precio nuevo, fecha y quién lo motivó.

### Backup BD

![Backup de base de datos](manual/images/17_mantenimiento_backup.png)

Genera una copia de seguridad de la base de datos al momento: descargarla, enviarla por email, o restaurar una copia anterior. Hay además un backup automático programado cada noche a las 23:00.

---

## Búsqueda global

![Búsqueda global](manual/images/18_busqueda_resultados.png)

Un único buscador (arriba del todo, en cualquier pantalla) que busca a la vez en clientes, trasteros, pisos, fianzas, gastos y pagos, y agrupa los resultados por tipo.
