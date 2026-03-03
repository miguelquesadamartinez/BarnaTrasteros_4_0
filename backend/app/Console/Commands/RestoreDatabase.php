<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreDatabase extends Command
{
    protected $signature   = 'db:restore {filename? : Nombre del archivo de backup (ej: backup_2026-03-01_23-00-00.sql.gz)}';
    protected $description = 'Restaura la base de datos desde un backup de la carpeta mysql_bk';

    public function handle(): int
    {
        $dir   = base_path('mysql_bk');
        $files = glob($dir . '/*.sql.gz');

        if (empty($files)) {
            $this->error('No hay backups disponibles en mysql_bk.');
            return self::FAILURE;
        }

        $filename = $this->argument('filename');

        if (!$filename) {
            $choices  = array_map('basename', $files);
            rsort($choices);
            $filename = $this->choice('Selecciona el backup a restaurar:', $choices, 0);
        }

        $filepath = $dir . '/' . basename($filename);

        if (!file_exists($filepath)) {
            $this->error("El archivo '{$filename}' no existe en mysql_bk.");
            return self::FAILURE;
        }

        $host     = config('database.connections.mysql.host');
        $port     = config('database.connections.mysql.port', 3306);
        $db       = config('database.connections.mysql.database');
        $user     = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $this->warn("Restaurando base de datos '{$db}' desde '{$filename}'...");

        // Intentar con cliente mysql/mariadb si está disponible
        $mysqlBin = $this->findBinary(['mysql', 'mariadb']);

        if ($mysqlBin) {
            $cmd = sprintf(
                'zcat %s | MYSQL_PWD=%s %s -h%s -P%s -u%s %s 2>&1',
                escapeshellarg($filepath),
                escapeshellarg($password),
                escapeshellarg($mysqlBin),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                escapeshellarg($db)
            );
            exec($cmd, $output, $exitCode);

            if ($exitCode === 0) {
                $this->info("Base de datos restaurada correctamente desde: {$filename}");
                return self::SUCCESS;
            }
            $this->warn('Cliente mysql no disponible o con error, usando fallback PHP PDO...');
        }

        // Fallback: restaurar vía PHP PDO
        try {
            $sql = implode('', gzfile($filepath));
            $pdo = DB::getPdo();

            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

            // Dividir en sentencias individuales y ejecutar
            $statements = array_filter(
                array_map('trim', preg_split('/;\s*$/m', $sql)),
                fn($s) => $s !== ''
            );

            foreach ($statements as $statement) {
                $pdo->exec($statement);
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

            $this->info("Base de datos restaurada correctamente desde: {$filename}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error al restaurar: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function findBinary(array $names): ?string
    {
        foreach ($names as $name) {
            exec("which {$name} 2>/dev/null", $out, $code);
            if ($code === 0 && !empty($out[0])) {
                return trim($out[0]);
            }
        }
        return null;
    }
}
