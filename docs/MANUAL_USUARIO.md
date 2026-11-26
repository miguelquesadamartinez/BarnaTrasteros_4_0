# Manual de Usuario — BarnaTrasteros

Guía visual de todas las pantallas y funciones de la aplicación. Uso interno, sin login: cualquiera con acceso a `localhost:5173` puede usar cualquier función.

Formatos que usa toda la aplicación: fechas en **dd/mm/aaaa**, importes en euros con coma decimal (**1.234,50 €**) y porcentajes como **+1,30 %**. Los buscadores no distinguen mayúsculas ni tildes ("sotano" encuentra "Sótano").

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
- [Uso desde el móvil](#uso-desde-el-móvil)

---

## Panel de Control (Inicio)

![Panel de Control](manual/images/00_inicio_panel_control.png)

Primera pantalla al entrar. De un vistazo:

- **Tarjetas superiores**: trasteros alquilados/libres, pisos alquilados/libres, total pendiente de cobro en alquileres, total pendiente en gastos. Se actualizan solas al registrar o anular un cobro.
- **Tabla "Pagos Pendientes"**: todos los alquileres (trastero o piso) con algo pendiente de cobro, del mes más reciente al más antiguo. Se puede filtrar por texto (nombre, apellidos o DNI, sin importar tildes), por tipo (trastero/piso) y cambiar cuántas filas se ven por página. La fila **Totales** suma solo la página que se ve.
- **Acciones por fila**:
  - **Pagar** — abre el modal de registrar un cobro para ese mes.
  - **Ver** — abre el detalle de los pagos ya registrados de esa unidad/mes (recibos en PDF, reenviar por email).
  - Icono de **hoja** — imprime el recibo de lo cobrado ese mes (solo aparece si ya se ha cobrado algo; si el cobro es parcial, el recibo indica lo pendiente y su total es lo realmente cobrado).
  - Icono de **campana** — envía al cliente un email recordándole que tiene un pago pendiente.

---

## Trasteros

![Listado de trasteros](manual/images/01_trasteros_lista.png)

Listado con todos los trasteros: número, piso, tamaño, precio/mes, cliente asignado y estado (**ALQUILADO** / **LIBRE**). Buscador por número, piso, tamaño o nombre del cliente.

**Acciones por fila:**
- **Editar** — abre el formulario de edición (mismos campos que "Nuevo Trastero", más los de abajo).
- **Dar de baja** (solo si está alquilado) — libera la unidad.
- **Eliminar** — borra el trastero. Solo se puede si está libre y **nunca ha tenido pagos ni fianzas**; si tiene historial, se conserva para no perderlo (y para que no queden recibos de una unidad que ya no existe).

### Nuevo Trastero / Editar Trastero

![Formulario nuevo piso](manual/images/02b_pisos_nuevo_modal.png)
*(el formulario de Trastero es idéntico al de Piso, solo cambia "Piso" por "Tamaño")*

Campos: número, piso/planta, tamaño (del catálogo de Mantenimiento), precio mensual, cliente asignado (buscador), notas.

Al **editar** un trastero ya alquilado aparecen además:

![Editar piso con cliente asignado](manual/images/02c_pisos_editar_modal.png)
*(igual en Trastero)*

- **Fecha inicio alquiler** y **Fecha de vencimiento** — la fecha de vencimiento marca a partir de cuándo se considera atrasado el pago si no se ha cobrado; por defecto es un mes después del inicio, pero se puede ajustar a mano.

### Asignar un trastero libre a un cliente nuevo

Al elegir un cliente para una unidad libre (desde aquí o desde la ficha del Cliente):

- La **fecha de inicio** se rellena con la de hoy y es obligatoria; la **fecha de vencimiento** se propone un mes después.
- Se pide el **importe a cobrar este mes**, prorrateado según los días que quedan del mes desde la fecha de inicio, con el precio mensual completo como referencia. Si cambias la fecha de inicio, el importe se recalcula solo. Es editable antes de guardar.

Una unidad **ya alquilada no se puede pasar directamente a otro cliente**: primero hay que darla de baja. Tampoco se puede asignar a un cliente archivado.

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

Listado con nombre, DNI, teléfono, trastero(s)/piso asignados, si tiene foto de DNI subida. Buscador por nombre, apellido o DNI (sin importar tildes).

**Acciones por fila:**
- **Editar** — abre la ficha completa del cliente.
- **Archivar** — da de baja automáticamente todas sus unidades (avisando antes de lo pendiente) y lo oculta del listado sin borrar su historial. Aparece luego en Mantenimiento → Clientes Archivados.
- **Eliminar** — solo si el cliente no tiene pagos ni fianzas registradas; si los tiene, hay que usar Archivar en su lugar.

### Nuevo Cliente

![Formulario nuevo cliente](manual/images/03b_clientes_nuevo_modal.png)

- Datos básicos: nombre, apellido, DNI, teléfono, email, dirección, código postal, ciudad. Se comprueba el formato:
  - **DNI/NIE/CIF**: 12345678A, X1234567B o B12345678 (los espacios, puntos y guiones se quitan solos y la letra se pasa a mayúscula).
  - **Teléfono**: 9 cifras, o con prefijo internacional (+34…).
  - **Código postal**: 5 cifras.
  - **Email**: se guarda y se usa para enviar recibos, avisos de impago y facturas. Sin email, esos envíos no son posibles.
- **Necesita factura mensual** — si se marca, este cliente se incluye en la generación automática de facturas.
- **Foto del DNI** — JPG, PNG o PDF, máx. 5MB.
- **Propiedades asociadas** (opcional, se puede completar después): buscador para añadir trastero(s), buscador para asignar un piso.
- **Fianzas**: solo se pueden añadir una vez asignada al menos una unidad — importe (mayor que 0) y fecha.

Al guardar con alguna unidad asignada se genera automáticamente el contrato de alquiler en PDF y se abre en una pestaña nueva. Si el navegador bloquea la pestaña, el contrato se genera igualmente y aparece un aviso: **pulsa sobre el aviso para abrirlo**. Si el cliente no tiene ninguna unidad, no se genera contrato.

### Ficha de Cliente (Editar)

![Ficha de cliente con unidad asignada](manual/images/03c_clientes_editar_modal.png)

Además de los datos básicos:

- **Generar contrato** — crea (o regenera) el PDF del contrato con las unidades actuales.
- **Pago pendiente** — si hay algo pendiente, aparece el total y el botón **Avisar de pago pendiente** (envía un email al cliente).
- **Trasteros asignados / Piso asignado** — se pueden añadir o quitar desde aquí mismo.
- **Fecha de vencimiento del alquiler** — editable por unidad, con su propio botón Guardar.
- **Fianzas** — añadir importe y fecha. Si acabas de añadir la unidad en esta misma ficha, pulsa antes **Actualizar** para guardar la asignación y después añade la fianza (la pantalla te lo indica). Quitar una fianza (×) pide confirmación y la marca como devuelta: no la borra, pasa a Fianzas Devueltas y, **si el cliente tiene email, se le envía el justificante de devolución**.

### Asignar una unidad nueva desde la ficha del cliente

![Importe prorrateado al asignar unidad](manual/images/03d_clientes_asignar_unidad_prorateo.png)

Al añadir un trastero o piso libre aparece **"Importe a cobrar este mes"**, prorrateado por defecto según los días de alquiler de este mes, junto con el precio mensual completo como referencia — igual que al asignar desde Trasteros/Pisos directamente.

---

## Lista de Espera

![Lista de espera](manual/images/04_lista_espera.png)

Clientes interesados cuando no hay unidad libre del tamaño que buscan.

![Añadir a lista de espera](manual/images/04b_lista_espera_nuevo_modal.png)

- **+ Añadir**: nombre, teléfono (9 cifras o con prefijo +) y tamaño buscado. La fecha es la del día en que se añade.
- **Eliminar**: quita el registro manualmente.
- Los registros de más de 2 meses se borran solos cada noche.

---

## Fianzas

### Fianzas Activas

![Fianzas activas](manual/images/05_fianzas_activas.png)

Todas las fianzas cobradas y aún no devueltas, con el cliente y la unidad a la que están ligadas. Se pueden gestionar desde aquí (**+ Nueva Fianza**, **Editar**, **Devolver**, con confirmación) o desde la ficha del cliente. La unidad de la fianza tiene que ser una de las que el cliente tiene alquiladas.

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
- Icono **hoja** — imprimir recibo y **@** — enviarlo por email. Solo aparecen si ese mes ya tiene algún cobro: un recibo acredita lo cobrado, así que en un cobro parcial indica lo pendiente y su total es lo pagado.
- **Eliminar** (solo pendientes sin nada cobrado).

El recordatorio de pago pendiente por email se envía desde el Panel de Control (campana), la ficha del cliente o Avisar Impagos.

**+ Crear Registro de Pago** permite crear a mano el recibo de un mes: el importe debe ser mayor que 0, la unidad tiene que existir y no puede repetirse la misma unidad, cliente, mes y año.

### Registrar Pago

![Registrar pago](manual/images/07b_pagos_registrar_modal.png)

Importe a pagar (por defecto el pendiente total del cliente — el cobro se reparte automáticamente entre los meses más antiguos con deuda, sin poder superar el total pendiente), fecha de pago (por defecto hoy) y notas opcionales. Las notas se imprimen en el recibo PDF.

### Detalle de Pagos

![Detalle de pagos](manual/images/07c_pagos_detalle_modal.png)

Lista de los cobros ya registrados para ese recibo: fecha, importe, botón para el PDF del recibo y para reenviarlo por email, y eliminar un cobro concreto si se registró por error.

---

## Gastos

![Gestión de gastos](manual/images/08_gastos_lista.png)

Gastos del negocio (agua, luz, comunidad, mantenimiento, otros), opcionalmente ligados a un trastero o piso concreto (se muestra su número, p. ej. "trastero T-06"). Filtros por tipo, estado, mes y año.

**Acciones por fila:** registrar pago, ver pagos registrados, imprimir/enviar recibo general, ver imágenes adjuntas, editar, eliminar.

### Nuevo Gasto

![Nuevo gasto](manual/images/08b_gastos_nuevo_modal.png)

Tipo, descripción, referencia (trastero/piso si aplica), fecha de emisión, fecha de vencimiento e importe total.

Las **imágenes** (facturas escaneadas, fotos; JPG o PNG) se añaden después de crear el gasto con el botón de imágenes de la fila; se pueden subir y borrar varias seguidas.

Al **editar** un gasto que ya tiene pagos, el importe total no puede ser menor que lo ya pagado.

### Registrar Pago del Gasto

![Registrar pago de gasto](manual/images/08c_gastos_registrar_pago_modal.png)

Igual que en Pagos: admite pagos parciales — el gasto queda en estado **PARCIAL** hasta cubrir el total, luego pasa a **PAGADO**.

---

## Facturas

![Facturas del mes](manual/images/09_facturas.png)

Genera y envía las facturas mensuales a los clientes marcados como "Necesita factura mensual", a partir de los recibos de alquiler del mes elegido. Desde aquí se descarga el PDF o se envía por email directamente al cliente.

---

## Avisar Impagos

![Avisar impagos](manual/images/10_avisar_impagos.png)

La tabla muestra todos los recibos con algo pendiente. El botón **Enviar aviso a todos** (y la campana de cada fila) manda el email de aviso a los clientes, pero **solo incluye los meses vencidos hace más de 5 días** (según la fecha de vencimiento de cada unidad); un mes que aún no ha vencido no genera aviso. Es la versión manual del envío automático que se hace cada día a las 09:00 con el mismo criterio.

---

## Relatorios

![Relatorios — pestaña Trasteros](manual/images/11_relatorios.png)

Cuatro pestañas (**Trasteros**, **Pisos**, **Pagos**, **Gastos**) con el estado completo de cada área: totales, alquilado/libre, cliente y fecha de inicio (columna "Desde") por unidad. Botón **Recargar** para refrescar los datos sin salir de la página.

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

Los recibos de cada mes se generan **solos**: la aplicación lo comprueba cada hora, así que si el ordenador estaba apagado el día 1, se generan en cuanto vuelve a encenderse (nunca se duplican, y se envía un email de reporte solo si se crea alguno).

Desde aquí se pueden generar a mano los de un mes concreto, por ejemplo uno pasado que se quedó sin generar:

- Solo el mes actual o meses anteriores (**no se pueden generar meses futuros**).
- Se usa el inquilino y el precio actuales de cada unidad, pero **no se cobra un mes anterior a la fecha de inicio del alquiler**, y si el alquiler empezó a mitad de ese mes se cobra la parte proporcional.
- Si el recibo de una unidad ya existe, no se toca.

### Revisión de Precio

![Revisión de precio](manual/images/15_mantenimiento_revision_precio.png)

Cambia el precio mensual de todas las unidades a la vez (un porcentaje, con confirmación) o de una en una (**Ir a cambio individual**: el botón Guardar de cada fila aplica el cambio al momento). En ambos casos **se envía un email a cada cliente afectado** con su nuevo precio y un resumen interno. Cada cambio queda registrado en el Historial de Precios. Los recibos ya generados no cambian; el nuevo precio se aplica desde el siguiente.

### Historial de Precios

![Historial de precios](manual/images/16_mantenimiento_historial_precios.png)

Registro de todos los cambios de precio hechos desde "Revisión de Precio": fecha y hora, unidad, precio anterior, precio nuevo, diferencia, porcentaje y motivo (revisión general o actualización manual).

### Copias de seguridad

![Copias de seguridad](manual/images/17_copias_seguridad.jpg)

Arriba, un resumen: cuándo se hizo la última copia (con un aviso si hace más de 2 días que no hay una completa), cuántas copias hay y cuánto ocupan, la copia automática y los datos actuales.

**Tipos de copia**

![Crear copia y listado](manual/images/17b_copias_crear_y_listado.jpg)

| Tipo | Qué incluye | Para qué |
|---|---|---|
| 🗂️ **Completa** (recomendada) | Base de datos + archivos (fotos de DNI, contratos PDF, imágenes de gastos) | Recuperarlo todo, por ejemplo en un ordenador nuevo |
| 🗄️ **Solo base de datos** | Clientes, unidades, pagos, gastos, fianzas… | Copia rápida y pequeña antes de un cambio grande |
| 🖼️ **Solo archivos** | Los archivos subidos a la aplicación | Guardar o recuperar solo los documentos |

Elige el tipo y pulsa **Crear copia**. Cada noche a las **23:00** se hace sola una **copia completa**; se guardan las últimas 30 automáticas y las más antiguas se borran solas. Las copias manuales, las subidas y las antiguas nunca se borran solas.

**Copias disponibles**

Se pueden filtrar por tipo (Completas, Base de datos, Archivos) o por origen (Automáticas, Antes de restaurar). Cada copia indica su fecha, tamaño, qué contiene (clientes, pagos, gastos y número de archivos) y su origen: **Manual**, **Automática**, **Antes de restaurar**, **Subida** o **Antigua** (las del sistema anterior, solo base de datos). Acciones:

- **♻️ Restaurar** — ver abajo.
- **⬇️ Descargar** — guarda la copia en el ordenador (conviene llevar de vez en cuando una copia completa a un pendrive o a la nube).
- **🔍 Comprobar** — lee la copia entera y confirma que no está dañada (✔ Íntegra / ✖ Dañada).
- **📧 Email** — la envía al email configurado (solo copias de menos de 20 MB).
- **🗑️ Eliminar** — con confirmación.

**⬆️ Subir copia** permite traer una copia guardada en el ordenador (un `.zip` o `.sql.gz` hecho por BarnaTrasteros, hasta 50 MB). Se comprueba al subirla y después se puede restaurar como cualquier otra. Es la forma de recuperar los datos en una instalación nueva.

**Restaurar**

![Restaurar copia](manual/images/17c_copias_restaurar.jpg)

1. Marca qué quieres recuperar: **Base de datos**, **Archivos** o ambos (solo se puede marcar lo que la copia contiene). Por ejemplo, de una copia completa puedes restaurar solo los archivos sin tocar los datos.
2. Deja marcada la opción **Guardar antes una copia del estado actual**: así, si te equivocas de copia, puedes volver atrás restaurando esa copia "Antes de restaurar" (se guardan las últimas 10).
3. Escribe **RESTAURAR** y confirma. Al terminar, pulsa **Recargar la aplicación** para ver los datos restaurados.

Antes de restaurar, la copia se comprueba entera; si está dañada no se toca nada.

---

## Búsqueda global

![Búsqueda global](manual/images/18_busqueda_resultados.png)

Pantalla **🔍 Búsqueda** del menú: un único buscador que busca a la vez en clientes, trasteros, pisos, fianzas, gastos y pagos, y agrupa los resultados por tipo.

---

## Uso desde el móvil

![Menú en el móvil](manual/images/19_menu_movil.jpg)

En pantallas estrechas (móvil o ventana pequeña) el menú se recoge en el botón **☰** de arriba a la derecha. Al pulsarlo aparecen todas las secciones; las que tienen ▾ se despliegan al tocarlas. El menú se cierra solo al entrar en una pantalla.

## Si una dirección no existe

Si se escribe a mano una dirección que no existe, aparece una página **404** con un botón para volver al Panel de Control.
