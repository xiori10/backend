<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UbigeoController extends Controller
{
    /**
     * Cargar archivo JSON de ubigeos
     * SIEMPRE devuelve un array
     */
    private function loadJSON(string $file): array
    {
        $path = "ubigeos/{$file}.json";

        if (!Storage::disk('public')->exists($path)) {
            abort(500, "No existe el archivo {$file}.json en storage/app/public/ubigeos");
        }

        $data = json_decode(
            Storage::disk('public')->get($path),
            true
        );

        if (!is_array($data)) {
            abort(500, "El archivo {$file}.json no tiene un formato válido");
        }

        return $data;
    }

    /**
     * Listar departamentos
     */
    public function departamentos()
    {
        $data = $this->loadJSON('departamentos');

        $departamentos = [];

        foreach ($data as $id => $nombre) {
            $departamentos[] = [
                'id' => $id,
                'nombre' => $nombre
            ];
        }

        return response()->json($departamentos);
    }

    /**
     * Listar provincias por departamento
     * ?departamento=01
     */
    public function provincias(Request $request)
    {
        $departamento = $request->query('departamento');

        if (!$departamento) {
            return response()->json([], 400);
        }

        $data = $this->loadJSON('provincias');

        if (!isset($data[$departamento])) {
            return response()->json([]);
        }

        $provincias = [];

        foreach ($data[$departamento] as $id => $nombre) {
            $provincias[] = [
                'id' => $id,
                'nombre' => $nombre,
                'departamento_id' => $departamento
            ];
        }

        return response()->json($provincias);
    }

    /**
     * Listar distritos por departamento y provincia
     * ?departamento=01&provincia=0104
     */
    // public function distritos(Request $request)
    // {
    //     $departamento = $request->query('departamento');
    //     $provincia = $request->query('provincia');

    //     if (!$departamento || !$provincia) {
    //         return response()->json([], 400);
    //     }

    //     $data = $this->loadJSON('distritos');

    //     if (!isset($data[$departamento][$provincia])) {
    //         return response()->json([]);
    //     }

    //     $distritos = [];

    //     foreach ($data[$departamento][$provincia] as $id => $nombre) {
    //         $distritos[] = [
    //             'id' => $id,
    //             'nombre' => $nombre,
    //             'provincia_id' => $provincia,
    //             'departamento_id' => $departamento
    //         ];
    //     }

    //     return response()->json($distritos);
    // }


    // public function distritos(Request $request)
    // {
    //     // En tu JSON, la provincia es un ID interno (ej: 2557)
    //     $provinciaId = $request->query('provincia');

    //     if (!$provinciaId) {
    //         return response()->json([], 400);
    //     }

    //     $data = $this->loadJSON('distritos');

    //     // El JSON está indexado por ID de provincia
    //     if (!isset($data[$provinciaId])) {
    //         return response()->json([]);
    //     }

    //     $distritos = [];

    //     foreach ($data[$provinciaId] as $item) {
    //         $distritos[] = [
    //             'id' => $item['id_ubigeo'],
    //             'nombre' => $item['nombre_ubigeo'],
    //             'codigo' => $item['codigo_ubigeo'],
    //             'provincia_id' => $item['id_padre_ubigeo'],
    //         ];
    //     }

    //     return response()->json($distritos);
    // }


    // public function distritos(Request $request)
    //     {
    //         $provincia = $request->query('provincia');

    //         if (!$provincia) {
    //             return response()->json([], 400);
    //         }

    //         $data = $this->loadJSON('distritos');

    //         // 🔴 CLAVE: el JSON está indexado por provincia
    //         if (!isset($data[$provincia])) {
    //             return response()->json([]);
    //         }

    //         $distritos = [];

    //         foreach ($data[$provincia] as $id => $nombre) {
    //             $distritos[] = [
    //                 'id_ubigeo' => $id,
    //                 'nombre_ubigeo' => $nombre['nombre_ubigeo'],
    //                 'provincia_id' => $provincia,
    //             ];
    //         }

    //         return response()->json($distritos);
    //     }


    public function distritos(Request $request)
{
    $provincia = $request->query('provincia');

    if (!$provincia) {
        return response()->json([], 400);
    }

    $data = $this->loadJSON('distritos');

    if (!isset($data[$provincia])) {
        return response()->json([]);
    }

    $distritos = [];

    foreach ($data[$provincia] as $item) {
        $distritos[] = [
            'id_ubigeo'   => $item['id_ubigeo'],
            'nombre_ubigeo' => $item['nombre_ubigeo'],
            'codigo_ubigeo' => $item['codigo_ubigeo'] ?? null,
            'nivel_ubigeo'  => $item['nivel_ubigeo'] ?? null,
            'provincia_id'  => $item['id_padre_ubigeo'],
        ];
    }

    return response()->json($distritos);
}


}
