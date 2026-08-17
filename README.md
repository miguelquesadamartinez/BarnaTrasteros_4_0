# BarnaTrasteros

Sistema de gestión para el negocio de alquiler de **trasteros** y **pisos** de Barna Trasteros. Uso interno, sin autenticación.

Backend **Laravel 12** · Frontend **Vue 3 + Vite + Pinia** · **MySQL 8** · **Docker Compose**.

---

## Funcionalidades

| Módulo | Qué hace |
|---|---|
| **Trasteros / Pisos** | Alta, edición, baja. Asignación de cliente y estado libre/alquilado. |
| **Clientes** | Alta con foto de DNI. Se les puede asignar trastero(s), piso y fianza desde el mismo formulario, tanto al crear como al editar. |
| **Contrato de alquiler** | Al crear un cliente con alguna unidad asignada se genera automáticamente un contrato en PDF y se abre en una pestaña nueva. Si más tarde cambia el trastero/piso asignado, se regenera solo. Desde la ficha del cliente se puede generar a mano o volver a consultarlo. |
| **Fianzas** | Ligadas siempre a un trastero/piso concreto del cliente. Listado de activas y de devueltas. |
| **Lista de espera** | Clientes interesados cuando no hay unidad libre del tamaño que buscan (nombre, teléfono, tamaño, fecha). Los registros de más de 2 meses se borran solos. |
| **Pagos** | Registro de cobros mensuales con reparto automático entre los meses más antiguos pendientes. Generación automática de los recibos de cada mes. |
| **Gastos** | Alta de gastos con pagos parciales e imágenes adjuntas. |
| **Facturas** | Generación mensual por cliente y envío por email con PDF adjunto. |
| **Relatorios** | Estado de trasteros, pisos, pagos y gastos con totales. |
| **Búsqueda global** | Un único buscador (menú superior) que busca a la vez en clientes, trasteros, pisos, fianzas, gastos y pagos. |
| **Mantenimiento** | Tamaños de trastero, generación manual de pagos, backup/restauración de la base de datos. |

---

## Automatizaciones (jobs programados)

| Cuándo | Qué hace |
|---|---|
| Día 1 de cada mes, 00:05 | Genera el pago pendiente de cada trastero/piso alquilado y manda un reporte a `REPORT_PAGOS_EMAIL`. |
| Todos los días, 09:00 | Revisa pagos pendientes vencidos hace más de 5 días (día 10 del mes) sin avisar aún, manda un email al cliente y un reporte a `REPORT_PAGOS_EMAIL` si hubo alguno. |
| Todos los lunes, 08:00 | Manda a `REPORT_PAGOS_EMAIL` un reporte con todos los pagos pendientes acumulados. |
| Todos los días, 10:00 | Borra de la lista de espera los registros con más de 2 meses. |
| Todos los días, 23:00 | Backup de la base de datos. |

Todos estos jobs también se pueden lanzar a mano:

```bash
docker compose exec backend php artisan pagos:generar [mes] [anyo]
docker compose exec backend php artisan pagos:avisar-impagos
docker compose exec backend php artisan pagos:reportar-pendientes
docker compose exec backend php artisan lista-espera:limpiar
docker compose exec backend php artisan db:backup
```

---

## Puesta en marcha

**Requisito:** Docker Desktop (con Docker Compose v2).

```bash
docker compose up -d
```

Servicios expuestos:

| Servicio | URL |
|---|---|
| Frontend | http://localhost:5173 |
| API backend | http://localhost:8000/api |
| phpMyAdmin | http://localhost:8080 (usuario `barnauser` / `barnapass`) |

Las migraciones y los datos iniciales **no se cargan solos** — la primera vez (o tras un `migrate:fresh`) hay que lanzarlos a mano:

```bash
# Crea las tablas + carga solo el catálogo de tamaños de trastero (seeder por defecto)
docker compose exec backend php artisan migrate --seed
```

Para tener una base de pruebas con datos de ejemplo (20 clientes, trasteros, pisos, pagos y gastos) en vez de la mínima:

```bash
docker compose exec backend php artisan db:seed --class="Database\Seeders\DemoDataSeeder" --force
```

```bash
# Regenera la base de dato con dato de prueba
docker compose exec backend php artisan migrate:fresh --seed --seeder="Database\Seeders\DemoDataSeeder"
```

> `migrate:fresh` (o restaurar un backup) modifica la base de datos por debajo de Eloquent y **no invalida la caché de Redis** — después de cualquiera de los dos, ejecuta `docker compose exec backend php artisan cache:clear` o los listados seguirán mostrando datos antiguos.

Cada seeder trunca y regenera solo las tablas que le corresponden (no son acumulativos):

| Seeder | Qué carga | Cuándo usarlo |
|---|---|---|
| `TamanyoTrasteroSeeder` (por defecto, vía `DatabaseSeeder`) | Solo el catálogo de tamaños de trastero | Base real, arranque limpio |
| `DemoDataSeeder` | Todo lo anterior + 20 clientes, 15 trasteros, 5 pisos, 45 pagos y 20 gastos de ejemplo | Base de datos local de pruebas |

Parar los servicios:

```bash
docker compose down          # conserva los datos
docker compose down -v       # también borra la base de datos
```

> Si se añade una variable de entorno o un fichero en `backend/config/`, hay que reiniciar tanto `backend` como `queue_worker` (`docker compose restart backend queue_worker`) para que ambos la recojan — el worker de colas mantiene el proceso vivo y no relee cambios hasta que se reinicia.

---

## Variables de entorno (`backend/.env`)

| Variable | Uso |
|---|---|
| `DB_*` | Conexión a MySQL |
| `MAIL_*` | SMTP para el envío de emails (recibos, facturas, avisos) |
| `REPORT_PAGOS_EMAIL` | Destinatario de los reportes automáticos de pagos |
| `EMPRESA_NOMBRE`, `EMPRESA_RESPONSABLE`, `EMPRESA_DNI_NIF`, `EMPRESA_DIRECCION`, `EMPRESA_TELEFONO` | Datos del arrendador que aparecen en el contrato de alquiler y en las cabeceras de los reportes |

---

## API — Endpoints principales

```
GET|POST|PUT|DELETE  /api/clientes
GET   /api/clientes/list-all
GET   /api/clientes/{id}/pendiente-total
POST  /api/clientes/{id}/contrato

GET|POST|PUT|DELETE  /api/trasteros
GET|POST|PUT|DELETE  /api/pisos
GET|POST|PUT|DELETE  /api/fianzas

GET|POST|DELETE  /api/lista-espera

GET   /api/pagos-alquiler
POST  /api/pagos-alquiler
POST  /api/pagos-alquiler/registrar-pago
DELETE /api/pagos-alquiler/{id}

GET|POST|PUT|DELETE  /api/gastos
POST  /api/gastos/{id}/pago
POST  /api/gastos/{id}/imagenes

GET   /api/facturas
POST  /api/facturas/enviar-email

GET   /api/relatorios/estado-trasteros
GET   /api/relatorios/estado-pisos
GET   /api/relatorios/estado-pagos
GET   /api/relatorios/estado-gastos
GET   /api/relatorios/resumen-general

GET   /api/busqueda?q=...
```
