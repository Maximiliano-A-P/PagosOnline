<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Búsqueda tolerante para los listados y autocompletados del admin.
 *
 * Reglas:
 * - El texto se parte en palabras. TODAS las palabras tienen que
 *   coincidir, cada una en CUALQUIERA de las columnas indicadas.
 *   Así "agustin" y "tomalto" encuentran a "Agustín Tomalto", y
 *   "tomalto agustin" también (el orden no importa).
 * - No distingue mayúsculas de minúsculas ni tildes/ñ/ü
 *   ("agustin" encuentra "Agustín", "munoz" encuentra "Muñoz").
 * - Coincide en cualquier parte del texto (no solo al principio).
 * - Las columnas numéricas (documento) se comparan como texto, y se
 *   ignoran puntos y guiones ("30.123.456" encuentra 30123456).
 *
 * Pensado para PostgreSQL. No requiere la extensión "unaccent":
 * usa translate() para sacar las tildes.
 */
class TextSearch
{
    /**
     * Letras con tilde (minúsculas) y su equivalente sin tilde.
     * Las mayúsculas se generan solas en map().
     */
    private const ACCENTS = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o',
        'ç' => 'c',
    ];

    /**
     * Mapa completo (minúsculas + mayúsculas).
     */
    private static function map(): array
    {
        $map = self::ACCENTS;

        foreach (self::ACCENTS as $from => $to) {
            $map[mb_strtoupper($from)] = strtoupper($to);
        }

        return $map;
    }

    /**
     * Pasa a minúsculas y saca las tildes.
     */
    public static function normalize(string $text): string
    {
        return mb_strtolower(strtr($text, self::map()));
    }

    /**
     * Expresión SQL de una columna de texto, ya sin tildes y en
     * minúsculas. $column lo define el programador (nunca el usuario).
     */
    private static function sqlText(string $column): string
    {
        $map  = self::map();
        $from = implode('', array_keys($map));
        $to   = implode('', array_values($map));

        return "lower(translate({$column}, '{$from}', '{$to}'))";
    }

    /**
     * Palabras de la búsqueda (normalizadas, sin repetir, máx. 6).
     */
    private static function tokens(?string $term): array
    {
        $term = self::normalize(trim((string) $term));

        if ($term === '') {
            return [];
        }

        $tokens = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY);

        return array_slice(array_values(array_unique($tokens)), 0, 6);
    }

    /**
     * Agrega al query las condiciones de búsqueda.
     *
     * @param Builder     $query         Consulta Eloquent a filtrar.
     * @param string|null $term          Lo que escribió el usuario.
     * @param string[]    $textColumns   Columnas de texto (nombre, servicio...).
     * @param string[]    $numberColumns Columnas numéricas (documento...).
     */
    public static function apply(
        Builder $query,
        ?string $term,
        array $textColumns,
        array $numberColumns = []
    ): Builder {
        foreach (self::tokens($term) as $token) {

            $query->where(function ($group) use (
                $token,
                $textColumns,
                $numberColumns
            ) {
                $like = '%' . addcslashes($token, '%_\\') . '%';

                foreach ($textColumns as $column) {
                    $group->orWhereRaw(
                        self::sqlText($column) . ' LIKE ?',
                        [$like]
                    );
                }

                /*
                 * Solo se busca en columnas numéricas si la palabra
                 * "parece un número" (dígitos, puntos, guiones).
                 */
                if (preg_match('/^[\d.\-\/]+$/', $token)) {
                    $digits = preg_replace('/\D+/', '', $token);

                    if ($digits !== '') {
                        foreach ($numberColumns as $column) {
                            $group->orWhereRaw(
                                "CAST({$column} AS TEXT) LIKE ?",
                                ['%' . $digits . '%']
                            );
                        }
                    }
                }
            });
        }

        return $query;
    }
}