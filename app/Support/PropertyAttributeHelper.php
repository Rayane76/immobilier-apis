<?php

namespace App\Support;

class PropertyAttributeHelper
{
    /**
     * Normalize a dynamic attribute key to a safe, ASCII Meilisearch field name.
     *
     * Examples:
     *   "nombre de pièces"  → "attr_nombre_de_pieces"
     *   "Parking sous sol"  → "attr_parking_sous_sol"
     *   "type de terrain"   → "attr_type_de_terrain"
     */
    public static function normalizeAttributeKey(string $key): string
    {
        $key = mb_strtolower($key);
        $key = strtr($key, [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'à' => 'a',
            'â' => 'a',
            'á' => 'a',
            'î' => 'i',
            'ï' => 'i',
            'í' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ó' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ú' => 'u',
            'ç' => 'c',
            'ñ' => 'n',
        ]);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        return 'attr_' . trim($key, '_');
    }
}
