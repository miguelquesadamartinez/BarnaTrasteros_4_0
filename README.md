# BarnaTrasteros 4.0

Sistema de gestión de alquileres de **trasteros** y **pisos**.  
Backend en **Laravel 11** · Frontend en **Vue 3 + Vite + Pinia** · Base de datos **MySQL 8** · Despliegue con **Docker Compose**.

---

## Cambios recientes (v4.0)

### Correcciones de despliegue
- **`.dockerignore` en backend**: excluye `public/storage` (symlink) del contexto de build de Docker, que causaba el error `invalid file request public/storage`.
- **`APP_KEY` válida en `docker-compose.yml`**: se reemplazó el placeholder `base64:placeholder_replace_after_install` por la clave real de 32 bytes, evitando que los contenedores no arrancaran o no ejecutaran migrate/seed.
- **CMD del Dockerfile más robusto**: `php artisan storage:link` usa `--force` y errores no fatales no interrumpen el arranque del servidor.

### Mejoras de rendimiento
- **Volumen nombrado `backend_vendor`**: la carpeta `vendor/` (miles de archivos PHP) se almacena en el filesystem nativo de Linux del contenedor en vez del bind mount Windows→WSL2, eliminando la latencia de E/S cruzada.
- **PHP OPcache activado**: los opcodes PHP se cachean en memoria (`256 MB`, `20.000 archivos`), evitando recompilaciones en cada request.
- **Polling en Vite**: añadido `watch.usePolling: true` en `vite.config.js` para que el HMR funcione correctamente en Docker/Windows.

### Correcciones de UI
- **Formato de fechas en Relatorios**: las columnas "Desde" (trasteros y pisos), "Emisión" y "Vencimiento" (gastos) ahora muestran `YYYY-MM-DD` en vez del timestamp ISO completo `YYYY-MM-DDTHH:mm:ss.SSSSSSZ`.

---

## Características

| Módulo | Funcionalidades |
|---|---|
| **Trasteros** | CRUD completo, asignación de cliente, filtro por estado (libre/alquilado) |
| **Pisos** | CRUD completo, asignación de cliente, filtro por estado |
| **Clientes** | CRUD con foto de DNI, visualización de propiedades asignadas |
| **Pagos** | Registro de pagos mensuales con distribución automática entre los meses más antiguos, sobrante calculado |
| **Gastos** | CRUD de gastos con registro de pagos parciales e imágenes adjuntas |
| **Relatorios** | Tablas de estado de trasteros, pisos, pagos y gastos con totales |
| **Job automático** | Generación de registros de pago el día 1 de cada mes a las 00:05 |

---

## Cómo funciona el Job de generación de pagos mensuales

### Visión general

Cada día 1 de mes a las 00:05, el sistema genera automáticamente un registro de pago pendiente por cada propiedad (trastero o piso) que tenga un cliente asignado en ese momento. El proceso es completamente automático, idempotente (nunca crea duplicados) y no requiere intervención manual.

---

### Flujo completo: de Docker a base de datos

```
┌─────────────────────────────────────────────────────────────────┐
│  Docker: contenedor barnatrasteros_scheduler                    │
│  Comando: php artisan schedule:work                             │
│  (bucle activo que comprueba el cron cada minuto)               │
└──────────────────────────┬──────────────────────────────────────┘
                           │ Día 1 de cada mes · 00:05
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  Laravel Scheduler  (routes/console.php)                        │
│  Schedule::job(new GenerarPagosMensuales)->monthlyOn(1, '00:05')│
│  → Pone el Job en la cola de base de datos                      │
└──────────────────────────┬──────────────────────────────────────┘
                           │ INSERT en tabla `jobs`
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  MySQL: tabla `jobs`  (cola de Laravel)                         │
│  Almacena el job serializado hasta que el worker lo procese     │
└──────────────────────────┬──────────────────────────────────────┘
                           │ Lectura inmediata (poll cada 3s)
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  Docker: contenedor barnatrasteros_queue                        │
│  Comando: php artisan queue:work --sleep=3 --tries=3            │
│  → Deserializa y ejecuta GenerarPagosMensuales::handle()        │
└──────────────────────────┬──────────────────────────────────────┘
                           │
           ┌───────────────┴────────────────┐
           ▼                                ▼
    Consulta `trasteros`            Consulta `pisos`
    WHERE cliente_id IS NOT NULL    WHERE cliente_id IS NOT NULL
           │                                │
           ▼                                ▼
    Por cada trastero alquilado:    Por cada piso alquilado:
    ¿Existe ya pago ese mes/año?    ¿Existe ya pago ese mes/año?
    Si NO → INSERT en              Si NO → INSERT en
    `pagos_alquiler`               `pagos_alquiler`
```

---

### Tablas implicadas

#### `trasteros`

| Columna | Tipo | Uso en el job |
|---|---|---|
| `id` | `bigint PK` | Se guarda como `referencia_id` en el pago |
| `cliente_id` | `bigint FK → clientes` | Si `NOT NULL` → la propiedad está alquilada y genera pago |
| `precio_mensual` | `decimal(8,2)` | Se copia como `importe_total` del pago |

#### `pisos`

Misma estructura relevante que `trasteros`: `id`, `cliente_id`, `precio_mensual`.

#### `pagos_alquiler` ← tabla donde se escriben los registros

| Columna | Tipo | Valor que inserta el job |
|---|---|---|
| `cliente_id` | `bigint FK → clientes` | El cliente asignado a la propiedad |
| `tipo` | `enum('trastero','piso')` | Tipo de propiedad |
| `referencia_id` | `bigint` | ID del trastero o piso |
| `mes` | `tinyint (1-12)` | Mes del pago generado |
| `anyo` | `year` | Año del pago generado |
| `importe_total` | `decimal(8,2)` | `precio_mensual` de la propiedad en ese momento |
| `pagado` | `decimal(8,2)` | `0` (aún no se ha cobrado nada) |
| `estado` | `enum` | `'pendiente'` |

> Índice único `(tipo, referencia_id, mes, anyo)` → garantiza que nunca se creen dos registros para la misma propiedad en el mismo mes/año, aunque el job se ejecute dos veces.

#### `detalle_pagos_alquiler` ← tabla auxiliar de pagos parciales

No la usa el job directamente. Se rellena cuando el usuario registra un cobro parcial o total desde la interfaz de Pagos. Cada fila representa un ingreso recibido contra un `pago_alquiler_id`.

| Columna | Descripción |
|---|---|
| `pago_alquiler_id` | FK al registro padre en `pagos_alquiler` |
| `importe` | Cantidad cobrada en ese pago parcial |
| `fecha_pago` | Fecha en que se recibió el dinero |

---

### Lógica anti-duplicados

Antes de insertar, el job comprueba:

```php
PagoAlquiler::where('tipo', 'trastero')
    ->where('referencia_id', $trastero->id)
    ->where('mes', $this->mes)
    ->where('anyo', $this->anyo)
    ->exists();
```

Si ya existe un registro → lo omite. Esto permite re-ejecutar el job manualmente sin riesgo.

---

### Ciclo de vida de un pago generado

```
Estado inicial:  pendiente  (pagado = 0)
                     │
    El usuario registra un cobro parcial
                     │
                  parcial   (0 < pagado < importe_total)
                     │
    El usuario registra el resto
                     │
                   pagado   (pagado >= importe_total)
```

El estado se recalcula en `PagoAlquiler::recalcularEstado()` cada vez que se registra un nuevo detalle de pago.

---

### Ejecución manual

```bash
# Generar pagos del mes actual
docker compose exec backend php artisan pagos:generar

# Generar pagos de un mes/año específico (ej: febrero 2026)
docker compose exec backend php artisan pagos:generar 2 2026
```

---

## Estructura del proyecto

```
BarnaTrasteros_3.0-Back-Front/
├── docker-compose.yml          # Orquestación completa (6 servicios)
├── docker/
│   └── mysql/
│       └── init.sql            # Inicialización de la base de datos
├── backend/                    # Laravel 11
│   ├── Dockerfile
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── ClienteController.php
│   │   │   ├── TrasteroController.php
│   │   │   ├── PisoController.php
│   │   │   ├── PagoAlquilerController.php
│   │   │   ├── GastoController.php
│   │   │   └── RelatorioController.php
│   │   ├── Jobs/
│   │   │   └── GenerarPagosMensuales.php
│   │   └── Models/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
│       ├── api.php
│       └── console.php
└── frontend/                   # Vue 3 + Vite
    ├── Dockerfile
    └── src/
        ├── api.js              # Instancia Axios
        ├── router/
        ├── stores/             # Pinia stores
        ├── components/         # SearchSelect, AppModal
        └── views/
            ├── HomeView.vue
            ├── trasteros/
            ├── pisos/
            ├── clientes/
            ├── pagos/
            ├── gastos/
            └── relatorios/
```

---

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/Mac/Linux)
- Docker Compose v2

---

## Puesta en marcha con Docker

### 1. Levantar todos los servicios

```bash
docker compose up -d
```

Esto inicia:
- **mysql** — MySQL 8 en puerto `3306`  
- **phpmyadmin** — Interfaz web en http://localhost:8080  
- **backend** — Laravel API en http://localhost:8000  
- **frontend** — Vue + Vite en http://localhost:5173  
- **scheduler** — Ejecuta el cron de generación de pagos  
- **queue_worker** — Procesa los jobs en cola  

> En el primer arranque, Laravel ejecutará automáticamente `migrate --force` y `db:seed --force`.

### 2. Abrir la aplicación

```
http://localhost:5173
```

### 3. Parar los servicios

```bash
docker compose down
```

Para eliminar también el volumen de datos de MySQL:

```bash
docker compose down -v
```

---

## Variables de entorno

### Backend (`backend/.env`)

| Variable | Valor por defecto | Descripción |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Driver de base de datos |
| `DB_HOST` | `mysql` | Host del contenedor MySQL |
| `DB_PORT` | `3306` | Puerto MySQL |
| `DB_DATABASE` | `barnatrasteros` | Nombre de la base de datos |
| `DB_USERNAME` | `barnauser` | Usuario MySQL |
| `DB_PASSWORD` | `barnapass` | Contraseña MySQL |
| `QUEUE_CONNECTION` | `database` | Cola de jobs |

### Frontend (`frontend/.env` opcional)

| Variable | Valor por defecto | Descripción |
|---|---|---|
| `VITE_API_BASE_URL` | `/api` (proxy) | URL base de la API |

---

## Ejecución sin Docker (desarrollo local)

### Backend

```bash
cd backend
composer install
cp .env.example .env
# Editar .env con los datos de tu MySQL local
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

---

## Comandos Artisan útiles

### Generar pagos manualmente

Genera los registros de pago para el mes y año indicados (o el mes actual si se omiten):

```bash
# Dentro del contenedor backend
docker compose exec backend php artisan pagos:generar

# Mes y año específicos
docker compose exec backend php artisan pagos:generar 3 2025
```

### Ejecutar seeders

```bash
docker compose exec backend php artisan db:seed --force
```

### Ver logs del scheduler

```bash
docker compose logs scheduler -f
```

### Ver logs del worker de colas

```bash
docker compose logs queue_worker -f
```

---

## API — Endpoints principales

```
GET    /api/clientes
POST   /api/clientes
GET    /api/clientes/{id}
PUT    /api/clientes/{id}
DELETE /api/clientes/{id}

GET    /api/trasteros
POST   /api/trasteros
...

GET    /api/pisos
POST   /api/pisos
...

GET    /api/pagos-alquiler
POST   /api/pagos-alquiler
POST   /api/pagos-alquiler/registrar-pago   ← distribución automática de pagos
DELETE /api/pagos-alquiler/{id}

GET    /api/gastos
POST   /api/gastos
PUT    /api/gastos/{id}
DELETE /api/gastos/{id}
POST   /api/gastos/{id}/pago
POST   /api/gastos/{id}/imagenes
DELETE /api/gastos/{id}/imagenes/{imagenId}

GET    /api/relatorios/estado-trasteros
GET    /api/relatorios/estado-pisos
GET    /api/relatorios/estado-pagos
GET    /api/relatorios/estado-gastos
GET    /api/relatorios/resumen-general
```

---

## PHPMyAdmin

Accesible en http://localhost:8080  
- **Servidor:** `mysql`  
- **Usuario:** `barnauser`  
- **Contraseña:** `barnapass`

---

## Diseño

- Paleta: fondo blanco `#ffffff`, acento rojo `#c0392b`
- Sin autenticación (uso interno)
- Responsive básico
