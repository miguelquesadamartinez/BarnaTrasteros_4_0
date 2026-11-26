<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Copias de seguridad de BarnaTrasteros.
 *
 * Tipos:
 *  - bd:       solo la base de datos      → bd_<fecha>[_origen].sql.gz (+ .json con el resumen)
 *  - archivos: solo los archivos subidos  → archivos_<fecha>[_origen].zip
 *  - completo: base de datos + archivos   → completo_<fecha>[_origen].zip
 *
 * Los .zip llevan un manifest.json con el resumen del contenido; dentro, la base
 * de datos va como database.sql.gz y los archivos bajo archivos/.
 * Las copias antiguas backup_<fecha>.sql.gz se siguen reconociendo como "bd".
 *
 * Origen (sufijo del nombre): sin sufijo = manual, _auto = nocturna,
 * _seguridad = creada antes de una restauración, _subido = subida desde el PC.
 */
class BackupService
{
    public const TIPOS = ['completo', 'bd', 'archivos'];

    private const PATRON = '/^(backup|bd|archivos|completo)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})(?:-(\d{1,6}))?(?:_(auto|seguridad|subido))?\.(sql\.gz|zip)$/';

    private const CACHE_LISTADO = 'backups:listado';

    // ─────────────────────────────── Rutas ───────────────────────────────

    public function directorio(): string
    {
        $dir = config('backups.directorio');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0775, true);
        }

        return $dir;
    }

    public function archivosPath(): string
    {
        return rtrim(config('backups.archivos_path'), '/');
    }

    /** Ruta absoluta de una copia, validando el nombre (evita rutas arbitrarias). */
    public function ruta(string $filename): string
    {
        $filename = basename($filename);
        if (!preg_match(self::PATRON, $filename)) {
            throw new RuntimeException("Nombre de copia no válido: {$filename}");
        }
        $ruta = $this->directorio() . '/' . $filename;
        if (!is_file($ruta)) {
            throw new RuntimeException("La copia '{$filename}' no existe.");
        }

        return $ruta;
    }

    // ─────────────────────────────── Crear ───────────────────────────────

    /**
     * @param string $tipo   completo | bd | archivos
     * @param string $origen manual | auto | seguridad
     */
    public function crear(string $tipo, string $origen = 'manual'): array
    {
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new RuntimeException("Tipo de copia no válido: {$tipo}");
        }

        $sello = now()->format('Y-m-d_H-i-s-u');
        $sufijo = $origen === 'manual' ? '' : "_{$origen}";
        $dir = $this->directorio();

        if ($tipo === 'bd') {
            $filename = "bd_{$sello}{$sufijo}.sql.gz";
            $ruta = "{$dir}/{$filename}";
            $resumenBd = $this->volcarBaseDatos($ruta);
            $manifest = $this->manifest('bd', $origen, $resumenBd, null);
            File::put($ruta . '.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $filename = "{$tipo}_{$sello}{$sufijo}.zip";
            $ruta = "{$dir}/{$filename}";
            $tmpSql = null;
            $resumenBd = null;

            $zip = new ZipArchive();
            if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el archivo de copia.');
            }

            try {
                if ($tipo === 'completo') {
                    $tmpSql = $this->rutaTemporal('sql.gz');
                    $resumenBd = $this->volcarBaseDatos($tmpSql);
                    $zip->addFile($tmpSql, 'database.sql.gz');
                }
                $resumenArchivos = $this->anyadirArchivosAlZip($zip);
                $manifest = $this->manifest($tipo, $origen, $resumenBd, $resumenArchivos);
                $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $zip->close();
            } catch (\Throwable $e) {
                @$zip->close();
                @unlink($ruta);
                throw $e;
            } finally {
                if ($tmpSql) {
                    @unlink($tmpSql);
                }
            }
        }

        Cache::forget(self::CACHE_LISTADO);
        $this->aplicarRetencion();

        return $this->info($filename);
    }

    // ─────────────────────────────── Listar ──────────────────────────────

    public function listar(): array
    {
        return Cache::remember(self::CACHE_LISTADO, now()->addMinutes(5), function () {
            $items = [];
            foreach (glob($this->directorio() . '/*') as $ruta) {
                $nombre = basename($ruta);
                if (preg_match(self::PATRON, $nombre)) {
                    $items[] = $this->info($nombre);
                }
            }
            usort($items, fn ($a, $b) => strcmp($b['fecha'], $a['fecha']));

            return $items;
        });
    }

    public function info(string $filename): array
    {
        $ruta = $this->ruta($filename);
        preg_match(self::PATRON, basename($filename), $m);
        [, $prefijo, $fecha, , $origen] = array_pad($m, 6, null);

        $manifest = $this->leerManifest($ruta);
        $tipo = $manifest['tipo'] ?? ($prefijo === 'backup' ? 'bd' : $prefijo);

        return [
            'filename'  => basename($filename),
            'tipo'      => $tipo,
            'origen'    => $origen ?: ($prefijo === 'backup' ? 'antigua' : 'manual'),
            'fecha'     => Carbon::createFromFormat('Y-m-d_H-i-s', $fecha)->format('Y-m-d H:i:s'),
            'bytes'     => filesize($ruta),
            'contiene'  => [
                'bd'       => in_array($tipo, ['completo', 'bd'], true),
                'archivos' => in_array($tipo, ['completo', 'archivos'], true),
            ],
            'resumen'   => [
                'bd'       => $manifest['contenido']['bd'] ?? null,
                'archivos' => $manifest['contenido']['archivos'] ?? null,
            ],
        ];
    }

    /** Estado actual (lo que se copiaría ahora) para la pantalla. */
    public function estadoActual(): array
    {
        return [
            'bd'       => $this->resumenBaseDatos(),
            'archivos' => $this->resumenArchivos(),
        ];
    }

    // ───────────────────────────── Verificar ─────────────────────────────

    /** Comprueba que la copia se puede leer entera y tiene lo que dice tener. */
    public function verificar(string $filename): array
    {
        $ruta = $this->ruta($filename);
        $info = $this->info($filename);
        $problemas = [];

        if (str_ends_with($ruta, '.sql.gz')) {
            $problemas = array_merge($problemas, $this->verificarSqlGz($ruta));
        } else {
            $zip = new ZipArchive();
            $res = $zip->open($ruta, ZipArchive::CHECKCONS);
            if ($res !== true) {
                return ['ok' => false, 'problemas' => ["El archivo .zip está dañado (código {$res})."]];
            }
            if ($zip->getFromName('manifest.json') === false) {
                $problemas[] = 'Falta manifest.json.';
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nombre = $zip->getNameIndex($i);
                if ($this->rutaZipPeligrosa($nombre)) {
                    $problemas[] = "Entrada no permitida: {$nombre}";
                    continue;
                }
                // Leer cada entrada fuerza la comprobación de su CRC.
                $stream = $zip->getStream($nombre);
                if (!$stream) {
                    $problemas[] = "No se puede leer: {$nombre}";
                    continue;
                }
                while (!feof($stream)) {
                    if (fread($stream, 1 << 20) === false) {
                        $problemas[] = "Error de lectura: {$nombre}";
                        break;
                    }
                }
                fclose($stream);
            }
            if ($info['contiene']['bd']) {
                $tmp = $this->rutaTemporal('sql.gz');
                $contenido = $zip->getFromName('database.sql.gz');
                if ($contenido === false) {
                    $problemas[] = 'Falta database.sql.gz.';
                } else {
                    File::put($tmp, $contenido);
                    $problemas = array_merge($problemas, $this->verificarSqlGz($tmp));
                    @unlink($tmp);
                }
            }
            $zip->close();
        }

        return ['ok' => empty($problemas), 'problemas' => $problemas];
    }

    // ───────────────────────────── Restaurar ─────────────────────────────

    /**
     * Restaura la base de datos y/o los archivos de una copia.
     * Antes, salvo que se indique lo contrario, crea una copia completa del
     * estado actual (origen "seguridad") para poder deshacer la restauración.
     */
    public function restaurar(string $filename, bool $bd, bool $archivos, bool $copiaPrevia = true): array
    {
        $ruta = $this->ruta($filename);
        $info = $this->info($filename);

        if (!$bd && !$archivos) {
            throw new RuntimeException('Indica qué quieres restaurar: base de datos, archivos o ambos.');
        }
        if ($bd && !$info['contiene']['bd']) {
            throw new RuntimeException('Esta copia no contiene base de datos.');
        }
        if ($archivos && !$info['contiene']['archivos']) {
            throw new RuntimeException('Esta copia no contiene archivos.');
        }

        $verificacion = $this->verificar($filename);
        if (!$verificacion['ok']) {
            throw new RuntimeException('La copia está dañada: ' . implode(' ', $verificacion['problemas']));
        }

        $seguridad = $copiaPrevia ? $this->copiaDeSeguridadPrevia() : null;

        $tmpSql = null;
        $tmpDir = null;
        try {
            if ($bd) {
                if (str_ends_with($ruta, '.sql.gz')) {
                    $sqlGz = $ruta;
                } else {
                    $tmpSql = $this->rutaTemporal('sql.gz');
                    $zip = $this->abrirZip($ruta);
                    File::put($tmpSql, $zip->getFromName('database.sql.gz'));
                    $zip->close();
                    $sqlGz = $tmpSql;
                }
                $this->restaurarBaseDatos($sqlGz);
            }

            if ($archivos) {
                $tmpDir = $this->rutaTemporal('dir');
                File::makeDirectory($tmpDir, 0775, true);
                $zip = $this->abrirZip($ruta);
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $nombre = $zip->getNameIndex($i);
                    if (!str_starts_with($nombre, 'archivos/') || str_ends_with($nombre, '/')) {
                        continue;
                    }
                    if ($this->rutaZipPeligrosa($nombre)) {
                        throw new RuntimeException("Entrada no permitida en la copia: {$nombre}");
                    }
                    $destino = $tmpDir . '/' . substr($nombre, strlen('archivos/'));
                    File::ensureDirectoryExists(dirname($destino));
                    File::put($destino, $zip->getFromIndex($i));
                }
                $zip->close();
                $this->sustituirArchivos($tmpDir);
            }
        } finally {
            if ($tmpSql) {
                @unlink($tmpSql);
            }
            if ($tmpDir) {
                File::deleteDirectory($tmpDir);
            }
            // Listados y relatorios están en Redis 24 h: tras restaurar mostrarían datos antiguos.
            Cache::flush();
        }

        return [
            'restaurado' => ['bd' => $bd, 'archivos' => $archivos],
            'copia_seguridad' => $seguridad['filename'] ?? null,
        ];
    }

    // ───────────────────────── Subir / eliminar ──────────────────────────

    /** Incorpora una copia subida desde el PC tras comprobar que es válida. */
    public function importar(string $rutaSubida, string $nombreOriginal): array
    {
        $sello = now()->format('Y-m-d_H-i-s-u');
        $nombreOriginal = strtolower($nombreOriginal);

        if (str_ends_with($nombreOriginal, '.sql.gz')) {
            $problemas = $this->verificarSqlGz($rutaSubida);
            if ($problemas) {
                throw new RuntimeException('El archivo no es una copia válida de base de datos: ' . implode(' ', $problemas));
            }
            $filename = "bd_{$sello}_subido.sql.gz";
        } elseif (str_ends_with($nombreOriginal, '.zip')) {
            $manifest = $this->leerManifest($rutaSubida);
            $tipo = $manifest['tipo'] ?? null;
            if (!in_array($tipo, ['completo', 'archivos'], true)) {
                throw new RuntimeException('El .zip no es una copia de BarnaTrasteros (falta o no es válido su manifest.json).');
            }
            $filename = "{$tipo}_{$sello}_subido.zip";
        } else {
            throw new RuntimeException('Solo se admiten copias .sql.gz o .zip generadas por BarnaTrasteros.');
        }

        File::copy($rutaSubida, $this->directorio() . "/{$filename}");
        Cache::forget(self::CACHE_LISTADO);

        $verificacion = $this->verificar($filename);
        if (!$verificacion['ok']) {
            $this->eliminar($filename);
            throw new RuntimeException('La copia subida está dañada: ' . implode(' ', $verificacion['problemas']));
        }

        return $this->info($filename);
    }

    public function eliminar(string $filename): void
    {
        $ruta = $this->ruta($filename);
        @unlink($ruta);
        @unlink($ruta . '.json');
        Cache::forget(self::CACHE_LISTADO);
    }

    /** Borra las copias automáticas y de seguridad más antiguas (las manuales y antiguas nunca). */
    public function aplicarRetencion(): array
    {
        $borradas = [];
        $limites = [
            'auto'      => max(1, (int) config('backups.retencion_automaticas')),
            'seguridad' => max(1, (int) config('backups.retencion_seguridad')),
        ];
        $todas = $this->listarSinCache();
        foreach ($limites as $origen => $limite) {
            $deEseOrigen = array_values(array_filter($todas, fn ($b) => $b['origen'] === $origen));
            foreach (array_slice($deEseOrigen, $limite) as $b) {
                $this->eliminar($b['filename']);
                $borradas[] = $b['filename'];
            }
        }

        return $borradas;
    }

    // ───────────────────────────── Internos ──────────────────────────────

    /**
     * Copia del estado actual antes de restaurar. En una instalación nueva (BD sin
     * tablas) no hay nada que salvar de la BD: no debe impedir la restauración.
     */
    private function copiaDeSeguridadPrevia(): ?array
    {
        $actual = $this->estadoActual();
        if ($actual['bd']['tablas'] > 0) {
            return $this->crear('completo', 'seguridad');
        }
        if ($actual['archivos']['total'] > 0) {
            return $this->crear('archivos', 'seguridad');
        }

        return null;
    }

    private function listarSinCache(): array
    {
        Cache::forget(self::CACHE_LISTADO);

        return $this->listar();
    }

    private function manifest(string $tipo, string $origen, ?array $bd, ?array $archivos): array
    {
        return [
            'aplicacion' => 'BarnaTrasteros',
            'version'    => 1,
            'tipo'       => $tipo,
            'origen'     => $origen,
            'creado'     => now()->toIso8601String(),
            'contenido'  => ['bd' => $bd, 'archivos' => $archivos],
        ];
    }

    private function leerManifest(string $ruta): ?array
    {
        if (str_ends_with($ruta, '.sql.gz')) {
            return is_file($ruta . '.json') ? json_decode(File::get($ruta . '.json'), true) : null;
        }
        $zip = new ZipArchive();
        if ($zip->open($ruta, ZipArchive::RDONLY) !== true) {
            return null;
        }
        $json = $zip->getFromName('manifest.json');
        $zip->close();
        $datos = $json ? json_decode($json, true) : null;

        return is_array($datos) && ($datos['aplicacion'] ?? null) === 'BarnaTrasteros' ? $datos : null;
    }

    private function abrirZip(string $ruta): ZipArchive
    {
        $zip = new ZipArchive();
        if ($zip->open($ruta, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('No se pudo abrir la copia.');
        }

        return $zip;
    }

    private function rutaTemporal(string $ext): string
    {
        $dir = storage_path('app/backup-tmp');
        File::ensureDirectoryExists($dir);

        return $dir . '/' . Str::random(16) . '.' . $ext;
    }

    /** Evita "zip slip": entradas con rutas absolutas o que salgan del destino. */
    private function rutaZipPeligrosa(string $nombre): bool
    {
        return str_starts_with($nombre, '/') || str_contains($nombre, '..') || str_contains($nombre, '\\');
    }

    // ── Base de datos ──

    private function credenciales(): array
    {
        $c = config('database.connections.mysql');

        return [$c['host'], $c['port'] ?? 3306, $c['database'], $c['username'], $c['password']];
    }

    /** Vuelca la BD a un .sql.gz y devuelve su resumen (tablas y filas). */
    private function volcarBaseDatos(string $destino): array
    {
        [$host, $port, $db, $user, $password] = $this->credenciales();
        $mysqldump = $this->binario(['mysqldump', 'mariadb-dump']);

        if ($mysqldump) {
            $cmd = sprintf(
                'MYSQL_PWD=%s %s --single-transaction --no-tablespaces --routines -h%s -P%s -u%s %s 2>/dev/null | gzip > %s',
                escapeshellarg($password), escapeshellarg($mysqldump), escapeshellarg($host),
                escapeshellarg((string) $port), escapeshellarg($user), escapeshellarg($db), escapeshellarg($destino)
            );
            exec($cmd);
        }

        // Si mysqldump no está o falló (en una tubería el código de salida es el de gzip,
        // así que se comprueba el contenido), se genera el volcado con PDO.
        if (!$mysqldump || $this->verificarSqlGz($destino)) {
            $gz = gzopen($destino, 'wb9');
            gzwrite($gz, $this->volcadoPdo($db));
            gzclose($gz);
        }

        if ($problemas = $this->verificarSqlGz($destino)) {
            throw new RuntimeException('El volcado de la base de datos no es válido: ' . implode(' ', $problemas));
        }

        return $this->resumenBaseDatos();
    }

    private function volcadoPdo(string $db): string
    {
        $pdo = DB::getPdo();
        $sql = "-- BarnaTrasteros Backup\n-- Date: " . now()->toDateTimeString() . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        $tablas = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tablas as $tabla) {
            $q = "`{$tabla}`";
            $create = $pdo->query("SHOW CREATE TABLE {$q}")->fetch(\PDO::FETCH_ASSOC);
            $sql .= "DROP TABLE IF EXISTS {$q};\n" . $create['Create Table'] . ";\n\n";
            $filas = $pdo->query("SELECT * FROM {$q}")->fetchAll(\PDO::FETCH_ASSOC);
            if ($filas) {
                $cols = '`' . implode('`, `', array_keys($filas[0])) . '`';
                $valores = array_map(
                    fn ($f) => '(' . implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $f)) . ')',
                    $filas
                );
                $sql .= "INSERT INTO {$q} ({$cols}) VALUES\n" . implode(",\n", $valores) . ";\n\n";
            }
        }

        return $sql . "SET FOREIGN_KEY_CHECKS=1;\n-- Dump completed\n";
    }

    /** Devuelve la lista de problemas (vacía si el .sql.gz es un volcado completo y legible). */
    private function verificarSqlGz(string $ruta): array
    {
        if (!is_file($ruta) || filesize($ruta) === 0) {
            return ['El volcado está vacío.'];
        }
        $gz = @gzopen($ruta, 'rb');
        if (!$gz) {
            return ['No es un archivo .gz válido.'];
        }
        $tieneTablas = false;
        $final = '';
        while (!gzeof($gz)) {
            $trozo = gzread($gz, 1 << 20);
            if ($trozo === false) {
                gzclose($gz);

                return ['El archivo comprimido está dañado.'];
            }
            if (!$tieneTablas && stripos($trozo, 'CREATE TABLE') !== false) {
                $tieneTablas = true;
            }
            $final = substr($final . $trozo, -200);
        }
        gzclose($gz);

        $problemas = [];
        if (!$tieneTablas) {
            $problemas[] = 'No contiene tablas.';
        }
        if (!str_contains($final, 'Dump completed')) {
            $problemas[] = 'El volcado está incompleto.';
        }

        return $problemas;
    }

    private function restaurarBaseDatos(string $sqlGz): void
    {
        [$host, $port, $db, $user, $password] = $this->credenciales();
        $mysql = $this->binario(['mysql', 'mariadb']);

        if ($mysql) {
            $cmd = sprintf(
                'gzip -dc %s | MYSQL_PWD=%s %s -h%s -P%s -u%s %s 2>&1',
                escapeshellarg($sqlGz), escapeshellarg($password), escapeshellarg($mysql),
                escapeshellarg($host), escapeshellarg((string) $port), escapeshellarg($user), escapeshellarg($db)
            );
            exec($cmd, $salida, $codigo);
            if ($codigo === 0) {
                return;
            }
        }

        // Alternativa sin cliente mysql: ejecutar las sentencias con PDO.
        $pdo = DB::getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (SqlSplitter::sentencias(implode('', gzfile($sqlGz))) as $sentencia) {
            $pdo->exec($sentencia);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function resumenBaseDatos(): array
    {
        $tablas = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $filas = 0;
        $detalle = [];
        foreach ($tablas as $t) {
            $nombre = array_values((array) $t)[0];
            $n = DB::table($nombre)->count();
            $detalle[$nombre] = $n;
            $filas += $n;
        }

        return [
            'tablas'   => count($detalle),
            'filas'    => $filas,
            'clientes' => $detalle['clientes'] ?? 0,
            'pagos'    => $detalle['pagos_alquiler'] ?? 0,
            'gastos'   => $detalle['gastos'] ?? 0,
        ];
    }

    // ── Archivos ──

    /** Archivos de usuario (se omiten los ocultos como .gitignore). */
    private function listarArchivos(): array
    {
        $base = $this->archivosPath();
        if (!File::isDirectory($base)) {
            return [];
        }

        return array_values(array_filter(
            File::allFiles($base),
            fn ($f) => !str_starts_with($f->getFilename(), '.')
        ));
    }

    private function resumenArchivos(): array
    {
        $archivos = $this->listarArchivos();
        $carpetas = [];
        $bytes = 0;
        foreach ($archivos as $f) {
            $carpeta = explode('/', str_replace('\\', '/', $f->getRelativePathname()))[0];
            $carpetas[$carpeta] = ($carpetas[$carpeta] ?? 0) + 1;
            $bytes += $f->getSize();
        }

        return ['total' => count($archivos), 'bytes' => $bytes, 'carpetas' => $carpetas];
    }

    private function anyadirArchivosAlZip(ZipArchive $zip): array
    {
        $zip->addEmptyDir('archivos');
        foreach ($this->listarArchivos() as $f) {
            $zip->addFile($f->getPathname(), 'archivos/' . str_replace('\\', '/', $f->getRelativePathname()));
        }

        return $this->resumenArchivos();
    }

    /** Sustituye el contenido de la carpeta de archivos por el restaurado (conserva los ocultos de la raíz). */
    private function sustituirArchivos(string $origen): void
    {
        $destino = $this->archivosPath();
        File::ensureDirectoryExists($destino);
        foreach (File::directories($destino) as $dir) {
            File::deleteDirectory($dir);
        }
        foreach (File::files($destino) as $f) {
            if (!str_starts_with($f->getFilename(), '.')) {
                File::delete($f->getPathname());
            }
        }
        File::copyDirectory($origen, $destino);
    }

    private function binario(array $nombres): ?string
    {
        foreach ($nombres as $n) {
            $ruta = trim((string) shell_exec('command -v ' . escapeshellarg($n) . ' 2>/dev/null'));
            if ($ruta !== '') {
                return $ruta;
            }
        }

        return null;
    }
}
