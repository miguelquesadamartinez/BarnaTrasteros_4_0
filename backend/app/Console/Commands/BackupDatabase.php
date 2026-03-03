<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature   = 'db:backup';
    protected $description = 'Genera un backup de la base de datos en la carpeta mysql_bk';

    public function handle(): int
    {
        $host     = config('database.connections.mysql.host');
        $port     = config('database.connections.mysql.port', 3306);
        $db       = config('database.connections.mysql.database');
        $user     = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $dir = base_path('mysql_bk');

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0775, true);
        }

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql.gz';
        $filepath = $dir . '/' . $filename;

        // Intentar con mysqldump si está disponible
        $mysqldump = $this->findBinary(['mysqldump', 'mariadb-dump']);

        if ($mysqldump) {
            $cmd = sprintf(
                'MYSQL_PWD=%s %s -h%s -P%s -u%s %s 2>/dev/null | gzip > %s',
                escapeshellarg($password),
                escapeshellarg($mysqldump),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                escapeshellarg($db),
                escapeshellarg($filepath)
            );
            exec($cmd, $output, $exitCode);

            if ($exitCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                $size = round(filesize($filepath) / 1024, 1);
                $this->info("Backup generado: {$filename} ({$size} KB)");
                return self::SUCCESS;
            }
        }

        // Fallback: generar SQL con PHP + PDO
        $this->info('Generando backup vía PHP PDO...');

        try {
            $pdo = DB::getPdo();
            $sql = $this->generateSqlDump($pdo, $db);

            $gz = gzopen($filepath, 'wb9');
            gzwrite($gz, $sql);
            gzclose($gz);

            $size = round(filesize($filepath) / 1024, 1);
            $this->info("Backup generado: {$filename} ({$size} KB)");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error al generar el backup: ' . $e->getMessage());
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

    private function generateSqlDump(\PDO $pdo, string $db): string
    {
        $sql  = "-- BarnaTrasteros Backup\n";
        $sql .= "-- Date: " . now()->toDateTimeString() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $quoted = "`{$table}`";

            // Estructura
            $create = $pdo->query("SHOW CREATE TABLE {$quoted}")->fetch(\PDO::FETCH_ASSOC);
            $sql   .= "DROP TABLE IF EXISTS {$quoted};\n";
            $sql   .= $create['Create Table'] . ";\n\n";

            // Datos
            $rows = $pdo->query("SELECT * FROM {$quoted}")->fetchAll(\PDO::FETCH_ASSOC);
            if ($rows) {
                $cols  = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $sql  .= "INSERT INTO {$quoted} ({$cols}) VALUES\n";
                $parts = [];
                foreach ($rows as $row) {
                    $vals   = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row);
                    $parts[] = '(' . implode(', ', $vals) . ')';
                }
                $sql .= implode(",\n", $parts) . ";\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }
}
