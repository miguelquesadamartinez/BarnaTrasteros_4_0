<?php

namespace App\Services;

class SqlSplitter
{
    /**
     * Divide un dump SQL en sentencias individuales, respetando el ';' cuando
     * aparece dentro de una cadena entre comillas (p. ej. una nota de cliente
     * que termine en ';' seguida de salto de línea). Un simple split por
     * ";\s*$" con regex rompería en ese caso.
     */
    public static function sentencias(string $sql): array
    {
        $statements = [];
        $current    = '';
        $inString   = false;
        $quoteChar  = '';
        $len        = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $current .= $char;

            if ($inString) {
                if ($char === '\\' && $i + 1 < $len) {
                    // Carácter escapado: consumir también el siguiente sin evaluarlo.
                    $current .= $sql[++$i];
                    continue;
                }
                if ($char === $quoteChar) {
                    $inString = false;
                }
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $inString  = true;
                $quoteChar = $char;
                continue;
            }

            if ($char === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $current = '';
            }
        }

        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}
