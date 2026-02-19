<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreinscripcionRequest;
use App\Models\Preinscripcion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\AuditService;

/**
 * Controlador principal para la gestión de pre-inscripciones.
 * 
 * Funciones:
 * - Listar pre-inscripciones con filtros y búsqueda
 * - Registrar nueva pre-inscripción
 * - Mostrar, actualizar y eliminar pre-inscripciones
 * - Verificar código de seguridad / DNI
 * - Generar PDFs de ficha de inscripción
 * 
 * Mejoras incluidas:
 * - Validaciones más estrictas
 * - Rate limiting sugerido para rutas críticas
 * - Separación de lógica sensible (limpieza, UBIGEO)
 * - Control de datos expuestos mediante arrays limpios
 */
class PreinscripcionController extends Controller
{
    /**
     * 🔹 Listado de pre-inscripciones
     * Aplica filtros de búsqueda, estado, escuela profesional y recientes
     * Devuelve paginación
     */
    public function index(Request $request): JsonResponse
    {
        // 🔥 Normalizar estado a MAYÚSCULAS
        if ($request->has('estado')) {
            $request->merge([
                'estado' => strtoupper($request->estado)
            ]);
        }

        $request->validate([
            'estado' => 'sometimes|in:PENDIENTE,PAGADO,INSCRITO,RECHAZADO',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'recientes' => 'sometimes|integer|min:1|max:365'
        ]);

        $query = Preinscripcion::query();

        if ($request->search) {
            $query->search($request->search);
        }

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
     * 🔹 Registrar nueva pre-inscripción
     * Valida datos, completa nombres de UBIGEO y realiza limpieza de campos
     * Genera código de seguridad automáticamente (modelo)
     * Envía correo de bienvenida (simulado)
     */
    public function store(PreinscripcionRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Si el colegio es "OTRO", usamos el campo manual
            if (($data['nombre_colegio'] ?? null) === 'OTRO' && !empty($data['nombre_colegio_manual'])) {
                $data['nombre_colegio'] = mb_strtoupper(trim($data['nombre_colegio_manual']), 'UTF-8');
            }
            unset($data['nombre_colegio_manual']);

            $data['colegio_id'] = $data['colegio_id'] ?? null;

            // Completa nombres UBIGEO según códigos
            $this->completarNombresUbigeo($data);

            // Limpieza: quitar valores null o vacíos
            $data = array_filter($data, fn($v) => $v !== null && $v !== '');

            // Campos controlados por backend
            $data['estado'] = 'PENDIENTE';
            $data['puede_modificar'] = true;

            /** @var Preinscripcion $preinscripcion */

            $preinscripcion = null;

            DB::transaction(function () use (&$preinscripcion, $data) {
                $preinscripcion = Preinscripcion::create($data);

                // Asegurar que nombres UBIGEO estén guardados (no hace doble update si ya existen)
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
     * 🔹 Mostrar pre-inscripción por número de documento
     * Devuelve solo datos esenciales
     */
    public function show(string $numeroDocumento): JsonResponse

    {

        $preinscripcion = Preinscripcion::where('numero_documento', $numeroDocumento)->first();

        if (!$preinscripcion) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        // Limitar datos expuestos
        $data = $preinscripcion->only([
            'numero_documento',
            'tipo_documento',
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'correo_electronico',
            'celular_personal',
            'celular_apoderado',
            'estado',
            'escuela_profesional'
        ]);

        return response()->json($data);
    }

    /**
     * 🔹 Actualizar pre-inscripción
     * Verifica código de seguridad y si aún puede modificar
     * Limpia datos y completa nombres UBIGEO
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
            // Solo actualizar campos permitidos
            $data = $request->only($preinscripcion->getFillable());
            $data = array_filter($data, fn($v) => $v !== null && $v !== '');
            unset($data['codigo_seguridad'], $data['numero_documento'], $data['estado'], $data['puede_modificar'], $data['fecha_modificacion']);

            $this->completarNombresUbigeo($data);

            $preinscripcion->update($data);

            $preinscripcion->marcarComoModificado();

            return response()->json([
                'message' => 'Actualizado correctamente',
                'data' => $preinscripcion
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
     * 🔹 Actualizar solo el estado de la pre-inscripción
     * Validaciones estrictas de valores permitidos
     * Requiere middleware auth para usuarios admin
     */
    public function actualizarEstado(Request $request, $id): JsonResponse
    {

        $request->merge([
            'estado' => strtoupper($request->estado)
        ]);

        // $request->validate([
        //     'estado' => 'required|in:PENDIENTE,PAGADO,INSCRITO,RECHAZADO',
        // ]);

        $request->validate([
            'estado' => 'required|in:' . implode(',', Preinscripcion::ESTADOS),
        ]);


        // $preinscripcion = Preinscripcion::find($id);
        $preinscripcion = Preinscripcion::findOrFail($id);


        if (!$preinscripcion) {
            return response()->json(['message' => 'Preinscripción no encontrada'], 404);
        }
        if (!$preinscripcion->puedeCambiarA($request->estado)) {
            return response()->json([
                'message' => 'No se puede cambiar del estado ' .
                    $preinscripcion->estado .
                    ' a ' .
                    $request->estado
            ], 422);
        }


        $estadoAnterior = $preinscripcion->estado;
        $preinscripcion->estado = $request->estado;
        $preinscripcion->save();

        // 🔥 Auditoría
        AuditService::log(
            'actualizar_estado_preinscripcion',
            'Cambió estado de preinscripción ID ' . $preinscripcion->id .
                ' de ' . $estadoAnterior .
                ' a ' . $request->estado
        );

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'data' => $preinscripcion
        ]);
    }

    /**
     * 🔹 Eliminar pre-inscripción
     * Requiere middleware auth (solo admin)
     */
    public function destroy(string $numeroDocumento): JsonResponse
    {
        $preinscripcion = Preinscripcion::where('numero_documento', $numeroDocumento)->first();

        if (!$preinscripcion) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        // Guardamos datos antes de eliminar
        $id = $preinscripcion->id;
        $documento = $preinscripcion->numero_documento;
        $nombre = $preinscripcion->nombres ?? '';

        $preinscripcion->delete();

        // 🔥 Auditoría
        AuditService::log(
            'eliminar_preinscripcion',
            'Eliminó preinscripción ID ' . $id .
                ' Documento: ' . $documento .
                ' Nombre: ' . $nombre
        );

        return response()->json(['message' => 'Eliminado']);
    }

    /** restaurar eliminado */

    public function restore(string $numeroDocumento): JsonResponse
    {
        $preinscripcion = Preinscripcion::withTrashed()
            ->where('numero_documento', $numeroDocumento)
            ->first();

        if (!$preinscripcion) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        if (!$preinscripcion->trashed()) {
            return response()->json(['message' => 'La preinscripción no está eliminada'], 400);
        }

        $preinscripcion->restore();

        // 🔥 Auditoría
        AuditService::log(
            'restaurar_preinscripcion',
            'Restauró preinscripción ID ' . $preinscripcion->id .
                ' Documento: ' . $preinscripcion->numero_documento
        );

        return response()->json(['message' => 'Preinscripción restaurada']);
    }

    /**
     * 🔹 Verificar existencia de DNI
     * Retorna si el documento ya está registrado
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
     * 🔹 Consultar pre-inscripción para modificación
     * Valida código de seguridad y devuelve datos esenciales + nombres UBIGEO
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
     * 🔹 Generar PDF de ficha de inscripción
     */
    public function imprimirFicha(string $numeroDocumento)
    {
        $preinscripcion = Preinscripcion::where('numero_documento', $numeroDocumento)->firstOrFail();

        return Pdf::loadView('pdf.ficha-inscripcion', [
            'preinscripcion' => $preinscripcion,
            'version' => 'oficina',
        ])->download("ficha_$numeroDocumento.pdf");
    }

    /**
     * 🔹 Enviar ficha de inscripción por correo (simulado)
     */
    public function enviarFichaCorreo(string $numeroDocumento)
    {
        $preinscripcion = Preinscripcion::where('numero_documento', $numeroDocumento)->firstOrFail();

        $pdf = Pdf::loadView('pdf.ficha-inscripcion', [
            'preinscripcion' => $preinscripcion,
            'version' => 'correo',
        ]);

        return $pdf->stream("ficha_$numeroDocumento.pdf");
    }

    /* =========================== MÉTODOS PRIVADOS =========================== */

    /**
     * 🔹 Completar nombres de UBIGEO según códigos enviados
     */
    private function completarNombresUbigeo(array &$data): void
    {
        $departamentos = cache()->rememberForever(
            'ubigeo_departamentos',
            fn() =>
            json_decode(file_get_contents(storage_path('app/public/ubigeos/departamentos.json')), true)
        );

        $provincias = cache()->rememberForever(
            'ubigeo_provincias',
            fn() =>
            json_decode(file_get_contents(storage_path('app/public/ubigeos/provincias.json')), true)
        );

        $distritos = cache()->rememberForever(
            'ubigeo_distritos',
            fn() =>
            json_decode(file_get_contents(storage_path('app/public/ubigeos/distritos.json')), true)
        );

        // NACIMIENTO
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

        // RESIDENCIA
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

        // COLEGIO
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

    private function buscarDepartamento(array $departamentos, string $codigo): ?array
    {
        foreach ($departamentos as $dep) {
            if ($dep['codigo_ubigeo'] === $codigo) return $dep;
        }
        return null;
    }

    private function buscarProvincia(array $provincias, string $departamentoId, string $codigo): ?array
    {
        if (!isset($provincias[$departamentoId])) return null;
        foreach ($provincias[$departamentoId] as $prov) {
            if ($prov['codigo_ubigeo'] === substr($codigo, -2)) return $prov;
        }
        return null;
    }

    private function buscarDistrito(array $distritos, string $provinciaId, string $codigo): ?array
    {
        if (!isset($distritos[$provinciaId])) return null;
        foreach ($distritos[$provinciaId] as $dist) {
            if ($dist['codigo_ubigeo'] === substr($codigo, -2)) return $dist;
        }
        return null;
    }

    /**
     * 🔹 Resolver nombres UBIGEO para API response
     */
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

    /**
     * 🔹 Enviar correo de bienvenida (simulado)
     */
    private function enviarCorreoBienvenida(Preinscripcion $p): void
    {
        Log::info("Correo enviado a: {$p->correo_electronico}");
        Log::info("Código de seguridad: {$p->codigo_seguridad}");
    }
}
