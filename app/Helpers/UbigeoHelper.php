<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class UbigeoHelper
{
    private static function loadJSON(string $file): array
    {
        $path = "ubigeos/{$file}.json";

        if (!Storage::disk('public')->exists($path)) {
            return [];
        }

        $data = json_decode(Storage::disk('public')->get($path), true);

        return is_array($data) ? $data : [];
    }

    public static function getDepartamentoNombre(string $codigo): string
    {
        $data = self::loadJSON('departamentos');
        return $data[$codigo] ?? $codigo;
    }

    public static function getProvinciaNombre(string $departamento, string $codigo): string
    {
        $data = self::loadJSON('provincias');
        return $data[$departamento][$codigo] ?? $codigo;
    }

    public static function getDistritoNombre(string $provincia, string $codigo): string
    {
        $data = self::loadJSON('distritos');

        if (isset($data[$provincia][$codigo]['nombre_ubigeo'])) {
            return $data[$provincia][$codigo]['nombre_ubigeo'];
        }

        return $codigo;
    }
}
