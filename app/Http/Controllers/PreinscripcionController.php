<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreinscripcionRequest;
use App\Models\Preinscripcion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class PreinscripcionController extends Controller
{
    /**
     * Listado
     */
    public function index(Request $request): JsonResponse
    {
        $query = Preinscripcion::query();

        if ($request->has('estado')) {
            $query->estado($request->estado);
        }

        if ($request->has('escuela_profesional')) {
            $query->escuelaProfesional($request->escuela_profesional);
        }

        if ($request->has('recientes')) {
            $query->recientes($request->input('recientes', 7));
        }

        return response()->json(
            $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15))
        );
    }

    /**
     * Registrar
     */


   

    public function store(PreinscripcionRequest $request): JsonResponse
    {
    
        try {
            // 1️⃣ Datos validados del formulario
            $data = $request->validated();

            // 2️⃣ Completar nombres UBIGEO (solo si hay códigos)
            $this->completarNombresUbigeo($data);


            // 3️⃣ 🔥 LIMPIEZA CLAVE (AQUÍ VA)
            $data = array_filter($data, fn ($v) => $v !== null && $v !== '');

            // 4️⃣ Campos controlados por backend
            $data['estado'] = 'PENDIENTE';
            $data['puede_modificar'] = true;

      /** @var Preinscripcion $preinscripcion */
                $preinscripcion = null;

                DB::transaction(function () use (&$preinscripcion, $data) {
                    $preinscripcion = Preinscripcion::create($data);

                    $preinscripcion->update([
                        'departamento_nacimiento_nombre' => $data['departamento_nacimiento_nombre'] ?? null,
                        'provincia_nacimiento_nombre'    => $data['provincia_nacimiento_nombre'] ?? null,
                        'distrito_nacimiento_nombre'     => $data['distrito_nacimiento_nombre'] ?? null,
                    ]);
                });


            $this->enviarCorreoBienvenida($preinscripcion);

            return response()->json([
                'message' => 'Pre-inscripción registrada exitosamente',
                'codigo_seguridad' => $preinscripcion->codigo_seguridad,
                // 'data' => $preinscripcion,
            ], 201);

        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'message' => 'Error al registrar la pre-inscripción',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Mostrar por documento
     */
    public function show(string $numeroDocumento): JsonResponse
    {
        $preinscripcion = Preinscripcion::where('numero_documento', $numeroDocumento)->first();

        if (!$preinscripcion) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json($preinscripcion);
    }

    /**
     * Actualizar
     */
    public function update(PreinscripcionRequest $request, string $numeroDocumento): JsonResponse
    {
        $preinscripcion = Preinscripcion::where('numero_documento', $numeroDocumento)->first();

        if (!$preinscripcion) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        if ($request->codigo_seguridad !== $preinscripcion->codigo_seguridad) {
            return response()->json(['message' => 'Código incorrecto'], 403);
        }

        if (!$preinscripcion->puedeModificar()) {
            return response()->json(['message' => 'Ya no puede modificar'], 403);
        }

        try {
            $data = $request->except('codigo_seguridad');

            // ✅ Volver a completar nombres UBIGEO
            $this->completarNombresUbigeo($data);

            // 🧹 limpieza
            $data = array_filter($data, fn ($v) => $v !== null && $v !== '');

            $preinscripcion->update($data);



            $preinscripcion->marcarComoModificado();

            return response()->json([
                'message' => 'Actualizado correctamente',
                'data' => $preinscripcion,
            ]);

        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Error al actualizar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar
     */
    public function destroy(string $numeroDocumento): JsonResponse
    {
        $preinscripcion = Preinscripcion::where('numero_documento', $numeroDocumento)->first();

        if (!$preinscripcion) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $preinscripcion->delete();

        return response()->json(['message' => 'Eliminado']);
    }

    /**
     * Verificar DNI
     */
    public function verificarDNI(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_documento' => 'required|in:DNI,CARNE_EXTRANJERIA',
            'numero_documento' => 'required|string',
        ]);

        $existe = Preinscripcion::where('numero_documento', $request->numero_documento)->exists();

        return response()->json([
            'existe' => $existe,
            'message' => $existe ? 'Documento ya registrado' : 'Disponible',
        ]);
    }

    /**
     * Consultar para modificar
     */
    public function consultarParaModificar(Request $request): JsonResponse
    {
        $request->validate([
            'numero_documento' => 'required|string',
            'codigo_seguridad' => 'required|string|size:5',
        ]);

        $p = Preinscripcion::where('numero_documento', $request->numero_documento)
            ->where('codigo_seguridad', $request->codigo_seguridad)
            ->first();

        if (!$p) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json([
            'data' => array_merge($p->toArray(), $this->resolverUbigeoNombres($p)),
            'puede_modificar' => $p->puedeModificar(),
        ]);
    }

    /**
     * PDF
     */

    public function imprimirFicha(string $numeroDocumento)
        {
            $preinscripcion = Preinscripcion::where('numero_documento', $numeroDocumento)->firstOrFail();

            return Pdf::loadView('pdf.ficha-inscripcion', [
                    'preinscripcion' => $preinscripcion,
                    'version' => 'oficina',
                ])
                ->download("ficha_$numeroDocumento.pdf");
        }


        public function enviarFichaCorreo(string $numeroDocumento)
        {
            $preinscripcion = Preinscripcion::where('numero_documento', $numeroDocumento)->firstOrFail();

            $pdf = Pdf::loadView('pdf.ficha-inscripcion', [
                'preinscripcion' => $preinscripcion,
                'version' => 'correo',
            ]);

            // aquí luego lo adjuntas al mail
            return $pdf->stream("ficha_$numeroDocumento.pdf");
        }



    /**
     * ===========================
     * 🔹 MÉTODOS PRIVADOS
     * ===========================
     */


        private function completarNombresUbigeo(array &$data): void
        {
            $departamentos = cache()->rememberForever('ubigeo_departamentos', fn() =>
                json_decode(file_get_contents(storage_path('app/public/ubigeos/departamentos.json')), true)
            );

            $provincias = cache()->rememberForever('ubigeo_provincias', fn() =>
                json_decode(file_get_contents(storage_path('app/public/ubigeos/provincias.json')), true)
            );

            $distritos = cache()->rememberForever('ubigeo_distritos', fn() =>
                json_decode(file_get_contents(storage_path('app/public/ubigeos/distritos.json')), true)
            );

            // ================= NACIMIENTO =================
            if (!empty($data['departamento_nacimiento'])) {

                $dep = $this->buscarDepartamento($departamentos, $data['departamento_nacimiento']);

                if ($dep) {
                    $prov = $this->buscarProvincia($provincias, $dep['id_ubigeo'], $data['provincia_nacimiento'] ?? null);

                    if ($prov) {
                        $dist = $this->buscarDistrito($distritos, $prov['id_ubigeo'], $data['distrito_nacimiento'] ?? null);

                        $data['departamento_nacimiento_nombre'] = $dep['nombre_ubigeo'];
                        $data['provincia_nacimiento_nombre']    = $prov['nombre_ubigeo'];
                        $data['distrito_nacimiento_nombre']     = $dist['nombre_ubigeo'] ?? null;
                    }
                }
            }

            // ================= RESIDENCIA =================
            // if (!empty($data['departamento_residencia'])) {

            //     $dep = $this->buscarDepartamento($departamentos, $data['departamento_residencia']);

            //     if ($dep) {
            //         $prov = $this->buscarProvincia($provincias, $dep['id_ubigeo'], $data['provincia_residencia'] ?? null);

            //         if ($prov) {
            //             $dist = $this->buscarDistrito($distritos, $prov['id_ubigeo'], $data['distrito_residencia'] ?? null);

            //             $data['departamento_residencia_nombre'] = $dep['nombre_ubigeo'];
            //             $data['provincia_residencia_nombre']    = $prov['nombre_ubigeo'];
            //             $data['distrito_residencia_nombre']     = $dist['nombre_ubigeo'] ?? null;
            //         }
            //     }
            // }


            // ================= RESIDENCIA =================
                // if (!empty($data['departamento_residencia'])) {

                //     $dep = $this->buscarDepartamento($departamentos, $data['departamento_residencia']);

                //     if ($dep) {
                //         $prov = $this->buscarProvincia($provincias, $dep['id_ubigeo'], $data['provincia_residencia'] ?? null);

                //         if ($prov) {
                //             $dist = $this->buscarDistrito($distritos, $prov['id_ubigeo'], $data['distrito_residencia'] ?? null);

                //             // 🔥 UBIGEO COMPLETO
                //             if ($dist) {
                //                 $data['ubigeo_residencia'] = $dist['id_ubigeo'];
                //             }

                //             $data['departamento_residencia_nombre'] = $dep['nombre_ubigeo'];
                //             $data['provincia_residencia_nombre']    = $prov['nombre_ubigeo'];
                //             $data['distrito_residencia_nombre']     = $dist['nombre_ubigeo'] ?? null;
                //         }
                //     }
                // }




                if (!empty($data['departamento_residencia'])) {

                $dep = $this->buscarDepartamento($departamentos, $data['departamento_residencia']);

                if ($dep) {
                    $prov = $this->buscarProvincia($provincias, $dep['id_ubigeo'], $data['provincia_residencia'] ?? null);

                    if ($prov) {
                        $dist = $this->buscarDistrito($distritos, $prov['id_ubigeo'], $data['distrito_residencia'] ?? null);

                        $data['departamento_residencia_nombre'] = $dep['nombre_ubigeo'];
                        $data['provincia_residencia_nombre']    = $prov['nombre_ubigeo'] ?? null;
                        $data['distrito_residencia_nombre']     = $dist['nombre_ubigeo'] ?? null;
                    }
                }
            }



                // ================= COLEGIO =================
                if (!empty($data['departamento_colegio'])) {

                    $dep = $this->buscarDepartamento($departamentos, $data['departamento_colegio']);

                    if ($dep) {
                        $prov = $this->buscarProvincia($provincias, $dep['id_ubigeo'], $data['provincia_colegio'] ?? null);

                        if ($prov) {
                            $dist = $this->buscarDistrito($distritos, $prov['id_ubigeo'], $data['distrito_colegio'] ?? null);

                            $data['departamento_colegio_nombre'] = $dep['nombre_ubigeo'];
                            $data['provincia_colegio_nombre']    = $prov['nombre_ubigeo'];
                            $data['distrito_colegio_nombre']     = $dist['nombre_ubigeo'] ?? null;
                        }
                    }
                }
        }




        // se aumenta hoy

        private function buscarDepartamento(array $departamentos, string $codigo): ?array
        {
            foreach ($departamentos as $dep) {
                if ($dep['codigo_ubigeo'] === $codigo) {
                    return $dep;
                }
            }
            return null;
        }


        private function buscarProvincia(array $provincias, string $departamentoId, string $codigo): ?array
        {
            if (!isset($provincias[$departamentoId])) {
                return null;
            }

            foreach ($provincias[$departamentoId] as $prov) {
                if ($prov['codigo_ubigeo'] === substr($codigo, -2)) {
                    return $prov;
                }
            }

            return null;
        }


        private function buscarDistrito(array $distritos, string $provinciaId, string $codigo): ?array
            {
                if (!isset($distritos[$provinciaId])) {
                    return null;
                }

                foreach ($distritos[$provinciaId] as $dist) {
                    if ($dist['codigo_ubigeo'] === substr($codigo, -2)) {
                        return $dist;
                    }
                }

                return null;
            }



        private function buscarDistritoNombre(array $distritos, ?string $idUbigeo): ?string
        {
            if (!$idUbigeo) return null;

            foreach ($distritos as $lista) {
                foreach ($lista as $d) {
                    if (
                        isset($d['id_ubigeo']) &&
                        $d['id_ubigeo'] === $idUbigeo
                    ) {
                        return $d['nombre_ubigeo'] ?? null;
                    }
                }
            }

            return null;
        }





    private function resolverUbigeoNombres(Preinscripcion $p): array
    {
        return [
            'departamento_nacimiento_nombre' => $p->departamento_nacimiento_nombre,
            'provincia_nacimiento_nombre'    => $p->provincia_nacimiento_nombre,
            'distrito_nacimiento_nombre'     => $p->distrito_nacimiento_nombre,
            'departamento_residencia_nombre' => $p->departamento_residencia_nombre,
            'provincia_residencia_nombre'    => $p->provincia_residencia_nombre,
            'distrito_residencia_nombre'     => $p->distrito_residencia_nombre,
        ];
    }

    private function enviarCorreoBienvenida(Preinscripcion $p): void
    {
        Log::info("Correo enviado a: {$p->correo_electronico}");
        Log::info("Código de seguridad: {$p->codigo_seguridad}");
    }
}
