<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ColegioController extends Controller
{

        private function normalizar($texto)
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = str_replace(
            ['á','é','í','ó','ú','ñ'],
            ['a','e','i','o','u','n'],
            $texto
        );
        return trim($texto);
    }

    public function listar($departamento, $provincia, $distrito)
    {
        // Ruta correcta para acceder al archivo en public/ubigeos
        $path = 'ubigeos/colegios.json';

        // Usamos 'public' disk para acceder a 'storage/app/public/ubigeos'
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['error' => "Archivo $path no encontrado"], 404);
        }

        // Cargar el archivo JSON
        $colegios = json_decode(Storage::disk('public')->get($path), true);

        if (!is_array($colegios)) {
            return response()->json(['error' => "JSON mal formado en $path"], 500);
        }

        // Filtrar por departamento, provincia y distrito
        // $resultado = array_filter($colegios, function($colegio) use ($departamento, $provincia, $distrito) {
        //     return strcasecmp($colegio['departamento'], $departamento) === 0
        //         && strcasecmp($colegio['provincia'], $provincia) === 0
        //         && strcasecmp($colegio['distrito'], $distrito) === 0;
        // });
        
        $resultado = array_filter($colegios, function($colegio) use ($departamento, $provincia, $distrito) {

            return $this->normalizar($colegio['departamento']) === $this->normalizar($departamento)
                && $this->normalizar($colegio['provincia']) === $this->normalizar($provincia)
                && $this->normalizar($colegio['distrito']) === $this->normalizar($distrito);
        });


        // Devolver los resultados
        return response()->json(array_values($resultado));
    }
}
