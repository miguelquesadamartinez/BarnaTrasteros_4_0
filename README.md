[Site](http://localhost:5173/)

# BarnaTrasteros 4.0

Sistema de gestión de alquileres de **trasteros** y **pisos**.  
Backend en **Laravel 11** · Frontend en **Vue 3 + Vite + Pinia** · Base de datos **MySQL 8** · Despliegue con **Docker Compose**.

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

## Migraciones de Laravel en Docker

Ejecutar migraciones pendientes:

```bash
docker compose exec backend php artisan migrate
```

Reejecutar migraciones desde cero (manteniendo estructura de tablas):

```bash
docker compose exec backend php artisan migrate:refresh
```

Borrar todas las tablas y recrearlas (incluyendo seeders):

```bash
docker compose exec backend php artisan migrate:fresh --seed
```

## Backups and restore

```bash
docker compose exec backend php artisan db:backup
```

```bash
docker compose exec backend php artisan db:restore
```

```bash
docker compose exec backend php artisan db:restore backup_2026-03-03_23-00-00.sql.gz
```

## Storage link

```bash
docker compose exec backend php artisan storage:link --force
```

## Instalar librerias

```bash
docker compose exec backend composer require predis/predis
```

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

## Librerias

npm install jspdf


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

## Cambios recientes (v4.0)

### Correcciones de despliegue
- **`.dockerignore` en backend**: excluye `public/storage` (symlink) del contexto de build de Docker, que causaba el error `invalid file request public/storage`.
- **`APP_KEY` válida en `docker-compose.yml`**: se reemplazó el placeholder `base64:placeholder_replace_after_install` por la clave real de 32 bytes, evitando que los contenedores no arrancaran o no ejecutaran migrate/seed.
- **CMD del Dockerfile más robusto**: `php artisan storage:link` usa `--force` y errores no fatales no interrumpen el arranque del servidor.

### `storage:link` en este proyecto

`php artisan storage:link` crea el enlace simbólico `public/storage` → `storage/app/public`.

Esto permite que los archivos subidos por el backend (por ejemplo, imágenes de gastos o documentos) se puedan servir por URL pública desde el frontend.

- Ruta física del archivo: `storage/app/public/...`
- URL pública resultante: `/storage/...`

En este proyecto se ejecuta en cada arranque del contenedor `backend` (en el `CMD` del `Dockerfile`):

```bash
(php artisan storage:link --force 2>/dev/null || true)
```

- `--force` recrea el enlace si ya existe.
- `2>/dev/null || true` evita que un fallo puntual del enlace tumbe el arranque completo del backend.

### Mejoras de rendimiento
- **Volumen nombrado `backend_vendor`**: la carpeta `vendor/` (miles de archivos PHP) se almacena en el filesystem nativo de Linux del contenedor en vez del bind mount Windows→WSL2, eliminando la latencia de E/S cruzada.
- **PHP OPcache activado**: los opcodes PHP se cachean en memoria (`256 MB`, `20.000 archivos`), evitando recompilaciones en cada request.
- **Polling en Vite**: añadido `watch.usePolling: true` en `vite.config.js` para que el HMR funcione correctamente en Docker/Windows.

### Correcciones de UI
- **Formato de fechas en Relatorios**: las columnas "Desde" (trasteros y pisos), "Emisión" y "Vencimiento" (gastos) ahora muestran `YYYY-MM-DD` en vez del timestamp ISO completo `YYYY-MM-DDTHH:mm:ss.SSSSSSZ`.

---

## Por qué el Dockerfile usa `php-fpm` en vez de `php artisan serve`

### `php artisan serve` — solo para desarrollo local

Es un servidor web minimalista incluido en Laravel, pensado exclusivamente para desarrollo local. Procesa las peticiones de forma **mono-proceso y secuencial**: no puede gestionar múltiples solicitudes simultáneas de forma eficiente y no está pensado para producción.

### `php-fpm` (FastCGI Process Manager) — estándar de producción

Es un gestor de procesos PHP robusto y multi-proceso. Gestiona un pool de workers PHP que atienden las peticiones en paralelo. Es el estándar en todos los entornos de producción.

### Arquitectura en este proyecto

En Docker, el backend usa dos contenedores que trabajan juntos:

```
Petición HTTP
      │
      ▼
┌─────────────┐     protocolo FastCGI     ┌──────────────────┐
│    Nginx    │ ────────────────────────▶│    PHP-FPM       │
│  (tráfico)  │◀─────────────────────────│  (lógica PHP)    │
└─────────────┘                           └──────────────────┘
```

- **Nginx** recibe las peticiones HTTP, sirve los archivos estáticos directamente (CSS, JS, imágenes) y delega las peticiones PHP a php-fpm mediante el protocolo FastCGI.
- **PHP-FPM** procesa el código Laravel y devuelve la respuesta a Nginx.

### Comparativa

| | `php artisan serve` | `php-fpm` |
|---|---|---|
| **Uso** | Desarrollo local | Producción |
| **Concurrencia** | 1 petición a la vez | Múltiples workers en paralelo |
| **Rendimiento** | Bajo | Alto |
| **Servidor web** | Integrado (básico) | Nginx/Apache por delante |
| **Archivos estáticos** | Los sirve PHP (lento) | Los sirve Nginx directamente |
| **Escalabilidad** | No | Configurable (`pm.max_children`) |

---

## Redis — Caché en memoria

### ¿Qué es Redis?

**Redis** (Remote Dictionary Server) es una base de datos en memoria de tipo clave-valor, de código abierto, extremadamente rápida. A diferencia de MySQL, los datos se almacenan en la RAM del servidor, lo que hace que las lecturas y escrituras sean del orden de **microsegundos** frente a los milisegundos de una base de datos relacional con disco.

#### Características principales

| Característica | Descripción |
|---|---|
| **Almacenamiento en RAM** | Los datos viven en memoria → latencias de 0.1–1 ms frente a 5–50 ms de MySQL |
| **Estructuras de datos ricas** | Strings, hashes, listas, sets, sorted sets, bitmaps, streams |
| **TTL por clave** | Cada entrada puede tener un tiempo de expiración automático |
| **Atomicidad** | Todas las operaciones son atómicas (hilo único con multiplexado I/O) |
| **Persistencia opcional** | RDB (snapshots) y AOF (append-only file) para no perder datos al reiniciar |
| **Pub/Sub** | Sistema de mensajería en tiempo real entre servicios |
| **Cache Tags** | Agrupación lógica de claves para invalidación masiva (via Redis Sets) |

#### Redis vs. caché de base de datos

Sin Redis, Laravel puede usar la tabla `cache` de MySQL para guardar resultados. La diferencia es radical:

```
Petición sin caché:
  Request → PHP → MySQL query (5–50ms) → Respuesta

Petición con caché en MySQL:
  Request → PHP → MySQL SELECT en tabla cache (3–15ms) → Respuesta
                  (ligeramente más rápido, pero sigue siendo disco)

Petición con caché en Redis:
  Request → PHP → Redis GET en RAM (0.1–1ms) → Respuesta
                  (40-500x más rápido en cache hits)
```

---

### Cómo está configurado en este proyecto

#### Contenedor Docker

El servicio `redis` está definido en `docker-compose.yml` y se levanta automáticamente junto al resto de servicios:

```yaml
redis:
  image: redis:7-alpine
  container_name: barnatrasteros_redis
  restart: unless-stopped
  networks:
    - barnatrasteros_network
```

- Usa la imagen oficial `redis:7-alpine` (mínima, sin herramientas innecesarias).
- No expone el puerto 6379 al host por seguridad (solo accesible dentro de la red Docker interna).
- El backend se conecta a él usando el hostname `redis` (nombre del servicio Docker).

#### Variables de entorno (`backend/.env`)

```dotenv
CACHE_STORE=redis

REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

| Variable | Valor | Descripción |
|---|---|---|
| `CACHE_STORE` | `redis` | Le indica a Laravel que use Redis como driver de caché |
| `REDIS_CLIENT` | `predis` | Librería PHP usada para hablar con Redis (alternativa a `phpredis` extension) |
| `REDIS_HOST` | `redis` | Hostname del contenedor Redis en la red Docker |
| `REDIS_PASSWORD` | `null` | Sin contraseña (entorno interno Docker) |
| `REDIS_PORT` | `6379` | Puerto por defecto de Redis |

#### Librería `predis`

Laravel soporta dos clientes para conectarse a Redis:

- **`phpredis`** — extensión C compilada (más rápida, requiere instalarla en el servidor PHP).
- **`predis`** — librería PHP pura (sin extensiones, fácil de instalar con Composer).

Este proyecto usa `predis`, instalado con:

```bash
docker compose exec backend composer require predis/predis
```

#### Configuración de caché (`backend/config/cache.php`)

```php
'default' => env('CACHE_STORE', 'database'),  // sobreescrito por .env a 'redis'

'stores' => [
    'redis' => [
        'driver'     => 'redis',
        'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
    ],
],
```

---

### Arquitectura de caché en BarnaTrasteros

#### Diagrama de flujo

```
Frontend (Vue 3)
      │ GET /api/pagos-alquiler?anyo=2026&mes=3
      ▼
Nginx (proxy)
      │
      ▼
PHP-FPM / Laravel
      │
      ├─── Construye cacheKey = md5("anyo=2026&mes=3&...")
      │
      ├─── Cache::tags(['pagos-alquiler'])->remember($key, 24h, fn)
      │         │
      │         ├── ¿Existe en Redis?
      │         │       │
      │         │    SÍ (cache HIT)          NO (cache MISS)
      │         │       │                        │
      │         │       ▼                        ▼
      │         │  Lee de Redis              Ejecuta query MySQL
      │         │  (0.1–1 ms)               (5–50 ms)
      │         │       │                        │
      │         │       │               Guarda resultado en Redis
      │         │       │               con TTL de 24 horas
      │         │       │                        │
      │         └───────┴────────────────────────┘
      │                          │
      ▼                          ▼
  response()->json($pagos)    (mismo resultado)
```

#### Tags de caché

Las **Tags** son grupos lógicos de claves en Redis. Permiten invalidar de golpe todas las claves de un grupo sin conocer su nombre exacto.

En este proyecto se usan 8 tags, una por dominio de datos:

| Tag | Claves que agrupa |
|---|---|
| `clientes` | Listados paginados, detalle, pendiente total por cliente |
| `trasteros` | Listados filtrados (search, libre), detalle por ID |
| `pisos` | Listados filtrados (search, libre), detalle por ID |
| `pagos-alquiler` | Listados paginados con todos los filtros, detalle por ID |
| `gastos` | Listados paginados, detalle por ID con imágenes |
| `facturas` | Listado de facturas por mes/año |
| `relatorio` | Estado trasteros, estado pisos, estado pagos, estado gastos, resumen general |
| `tamanyo-trasteros` | Lista completa de tamaños |

---

### Cómo se usa en el código

#### Patrón `remember` (lectura con caché automática)

```php
// PagoAlquilerController::index()

// 1. Construir clave única basada en los filtros de la petición
$cacheKey = 'pagos:list:' . md5(serialize($request->only([
    'tipo', 'referencia_id', 'cliente_id', 'cliente',
    'estado', 'anyo', 'mes', 'per_page', 'page',
])));

// 2. Intentar leer de Redis; si no existe, ejecutar la query y guardar
$pagos = Cache::tags(['pagos-alquiler'])->remember(
    $cacheKey,
    now()->addHours(24),   // TTL: 24 horas
    function () use ($request) {
        return PagoAlquiler::with(['cliente', 'detalles'])
            ->orderBy('anyo', 'desc')
            ->paginate($request->integer('per_page', 15));
    }
);
```

- La primera vez que se llama con unos filtros concretos → ejecuta la query MySQL y guarda en Redis.
- Las siguientes 24 horas con los mismos filtros → devuelve el resultado de Redis sin tocar MySQL.
- Si los filtros cambian (diferente `mes`, diferente `page`, etc.) → genera una clave diferente → nueva entrada en Redis.

#### Clave dinámica por filtros

Cada combinación única de parámetros genera su propia entrada en Redis. Por ejemplo:

```
pagos:list:<md5 de {anyo:2026}>                  → página 1, sin filtros extra
pagos:list:<md5 de {anyo:2026, mes:3}>            → filtrado por mes
pagos:list:<md5 de {anyo:2026, cliente_id:5}>     → filtrado por cliente
pagos:list:<md5 de {anyo:2026, per_page:50, page:2}> → paginación diferente
```

Todas estas entradas viven bajo el tag `pagos-alquiler`, por lo que un solo `flush()` las elimina todas.

#### `Cache::remember` vs `Cache::get` / `Cache::put`

| Método | Cuándo usarlo |
|---|---|
| `remember($key, $ttl, $fn)` | Lectura con caché automática (lo más habitual) |
| `get($key)` | Solo leer, sin lógica de "guardar si no existe" |
| `put($key, $value, $ttl)` | Guardar explícitamente un valor |
| `forget($key)` | Eliminar una clave concreta |
| `tags([...])->flush()` | **Eliminar todas las claves de un tag** ← el más usado aquí |

#### Invalidación por tags (`flush`)

Cada vez que se modifica un dato, se invalida la caché de todos los tags relacionados para que la siguiente lectura vaya a MySQL y obtenga los datos actualizados:

```php
// PagoAlquilerController::registrarPago() — tras registrar un cobro
Cache::tags(['pagos-alquiler', 'relatorio', 'facturas'])->flush();

// TrasteroController::update() — tras modificar un trastero
Cache::tags(['trasteros', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

// ClienteController::destroy() — tras eliminar un cliente
Cache::tags(['clientes', 'trasteros', 'pisos', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();
```

El flush es **inmediato y total**: todas las claves que tienen ese tag quedan eliminadas de Redis en esa misma petición. La siguiente request a cualquier endpoint que use esas tags irá a MySQL.

#### TTL diferenciado según volatilidad

No todos los datos cambian con la misma frecuencia:

| Dato | TTL | Justificación |
|---|---|---|
| Listados, detalles (pagos, trasteros, etc.) | 24 h | Datos estables, cambios siempre invalidan explícitamente |
| Pendiente total de un cliente | 10 min | Dato calculado que puede cambiar con más frecuencia |
| Lista de backups disponibles | 5 min | Los archivos de backup cambian raramente pero se listan con frecuencia |

---

### Invalidación cruzada — Mapa completo

Cuando se modifica un recurso, se invalidan en cascada todas las vistas que lo incluyen:

```
Acción                                Tags invalidadas
───────────────────────────────────────────────────────────────────
store/update/destroy Trastero    →   trasteros + clientes + relatorio + facturas + pagos-alquiler
store/update/destroy Piso        →   pisos + clientes + relatorio + facturas + pagos-alquiler
store/update/destroy Cliente     →   clientes + trasteros + pisos + relatorio + facturas + pagos-alquiler
store/destroy PagoAlquiler       →   pagos-alquiler + relatorio + facturas
registrarPago / eliminarDetalle  →   pagos-alquiler + relatorio + facturas
store/update/destroy Gasto       →   gastos + relatorio
registrarPago Gasto              →   gastos + relatorio
subirImagenes / eliminarImagen   →   gastos
store/update/destroy TamanyoTrastero → tamanyo-trasteros + trasteros
generarPagos (Job mensual)       →   pagos-alquiler + relatorio + facturas
backup / deleteBackup            →   mantenimiento:backups (forget key concreta)
```

---

### Comandos útiles para Redis

#### Dentro del contenedor Redis

```bash
# Abrir la CLI de Redis
docker compose exec redis redis-cli

# Ver todas las claves almacenadas
docker compose exec redis redis-cli keys "*"

# Ver las claves de caché de Laravel (incluyen el prefijo de la app)
docker compose exec redis redis-cli keys "barnatrasteros*"

# Inspeccionar el contenido de una clave
docker compose exec redis redis-cli get "barnatrasteros-cache-:pagos:list:abc123"

# Ver el TTL restante de una clave (en segundos; -1 = sin expiración; -2 = no existe)
docker compose exec redis redis-cli ttl "barnatrasteros-cache-:pagos:list:abc123"

# Vaciar toda la caché de Redis (¡elimina todo!)
docker compose exec redis redis-cli flushall

# Estadísticas de uso de Redis
docker compose exec redis redis-cli info stats

# Memoria usada por Redis
docker compose exec redis redis-cli info memory
```

#### Desde Artisan (Laravel)

```bash
# Vaciar toda la caché de la aplicación
docker compose exec backend php artisan cache:clear

# Ver el driver de caché activo
docker compose exec backend php artisan cache:show
```

---

### Diagnóstico: ¿Redis está funcionando?

#### 1. Comprobar que el contenedor está corriendo

```bash
docker compose ps
# Buscar: barnatrasteros_redis   Up
```

#### 2. Hacer ping a Redis desde el backend

```bash
docker compose exec backend php artisan tinker
# Dentro de tinker:
>>> Cache::put('test', 'ok', 60);
>>> Cache::get('test');   // debe devolver 'ok'
>>> Cache::forget('test');
```

#### 3. Ver si hay cache hits en los logs

Activa el log de queries de Laravel temporalmente y observa si las peticiones repetidas evitan la query SQL:

```php
// En AppServiceProvider::boot() — solo para debugging
\DB::listen(function ($query) {
    \Log::info($query->sql);
});
```

Si no ves la query SQL en el segundo request, la caché está funcionando correctamente.

#### 4. Comprobar conexión Redis desde tinker

```bash
docker compose exec backend php artisan tinker
>>> \Illuminate\Support\Facades\Redis::ping();
// Debe devolver: "+PONG"
```

---

### Instalación en desarrollo sin Docker

Si ejecutas el proyecto sin Docker y quieres usar Redis localmente:

#### Windows

```bash
# Opción 1: usar Memurai (Redis para Windows)
# https://www.memurai.com/

# Opción 2: usar WSL2 + Ubuntu
wsl
sudo apt update
sudo apt install redis-server
sudo service redis-server start
redis-cli ping   # debe devolver PONG
```

#### macOS

```bash
brew install redis
brew services start redis
redis-cli ping   # debe devolver PONG
```

#### Ubuntu / Debian

```bash
sudo apt update
sudo apt install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping   # debe devolver PONG
```

Luego en `backend/.env`:

```dotenv
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

E instalar `predis` si no está ya:

```bash
cd backend
composer require predis/predis
```

## Instalacion redis

```bash
docker compose exec backend composer require predis/predis
---
