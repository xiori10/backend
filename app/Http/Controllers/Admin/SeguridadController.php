<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\LoginAttemptService;
use App\Models\User;

class SeguridadController extends Controller
{
    /**
     * 🔹 Obtener todos los intentos de login
     * Ordenados por fecha (los más recientes primero)
     */
    public function loginAttempts(): JsonResponse
    {
        $attempts = DB::table('login_attempts')
            ->select('id', 'email', 'ip_address', 'successful', 'created_at')
            ->orderByDesc('created_at') // 🔹 Más preciso que usar id
            ->get();

        return response()->json($attempts);
    }

    /**
     * 🔹 Obtener todas las sesiones de usuario
     * Orden:
     * 1. Sesiones activas primero (logout_at = null)
     * 2. Por login_at descendente (las más recientes arriba)
     */
    public function sesiones(): JsonResponse
    {
        $sesiones = DB::table('user_sessions')
            ->join('users', 'users.id', '=', 'user_sessions.user_id')
            ->select(
                'user_sessions.id',      // 🔹 ID de la sesión
                'users.name',            // 🔹 Nombre del usuario
                'users.role',            // 🔹 Rol del usuario (admin/usuario)
                'user_sessions.ip_address',
                'user_sessions.login_at',
                'user_sessions.logout_at',
                'user_sessions.last_activity'
            )
            ->orderByRaw('logout_at IS NULL DESC') // 🔹 Activas primero
            ->orderByDesc('user_sessions.login_at')
            ->get();

        return response()->json($sesiones);
    }

    /**
     * 🔹 Obtener configuración de políticas
     * Si no existe, crear valores por defecto
     */
    public function configuracion(): JsonResponse
    {
        $config = DB::table('configuraciones')->first();

        if (!$config) {
            // 🔹 Valores por defecto
            DB::table('configuraciones')->insert([
                'tiempo_sesion' => 60,
                'max_intentos_login' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $config = DB::table('configuraciones')->first();
        }

        return response()->json($config);
    }

    /**
     * 🔹 Actualizar la configuración de políticas
     * Valida campos y guarda en la tabla configuraciones
     */
    public function actualizarConfiguracion(Request $request): JsonResponse
    {
        $request->validate([
            'tiempo_sesion' => 'required|integer|min:5|max:240',
            'max_intentos_login' => 'required|integer|min:1|max:10',
        ]);

        DB::table('configuraciones')->updateOrInsert(
            ['id' => 1],
            [
                'tiempo_sesion' => $request->tiempo_sesion,
                'max_intentos_login' => $request->max_intentos_login,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'), // 🔹 Mantener created_at si ya existe
            ]
        );

        return response()->json([
            'message' => 'Configuración actualizada correctamente'
        ]);
    }


    /**
     * 🔹 Obtener logs del sistema (auditoría)
     */
    // public function logs(): JsonResponse
    // {
    //     $logs = \App\Models\ActivityLog::with('user:id,name')
    //         ->select('id', 'user_id', 'action', 'description', 'ip_address', 'created_at')
    //         ->orderByDesc('created_at')
    //         ->limit(200)
    //         ->get();

    //     return response()->json($logs);
    // }

    // public function logs(Request $request): JsonResponse
    // {
    //     $query = \App\Models\ActivityLog::with('user:id,name');

    //     // 🔹 Filtro por usuario
    //     if ($request->filled('user')) {
    //         $query->whereHas('user', function ($q) use ($request) {
    //             $q->where('name', 'like', '%' . $request->user . '%');
    //         });
    //     }

    //     // 🔹 Filtro por acción
    //     if ($request->filled('action')) {
    //         $query->where('action', $request->action);
    //     }

    //     // 🔹 Filtro por fecha
    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
    //     }

    //     // 🔹 Orden y paginación
    //     $logs = $query->orderByDesc('created_at')->paginate(20); // 20 logs por página

    //     return response()->json($logs);
    // }


    public function logs(Request $request): JsonResponse
    {
        $query = \App\Models\ActivityLog::with('user:id,name');

        // 🔥 CAMBIO 1: ahora filtramos por user_id (más profesional)
        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        // 🔥 CAMBIO 2: filtro por acción exacta
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // 🔥 CAMBIO 3: mejor manejo de fechas (incluye todo el día)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $logs = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($logs);
    }

    // ✅ NUEVO ENDPOINT: LISTAR USUARIOS PARA EL SELECT
    public function users(): JsonResponse
    {
        $users = User::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    // ✅ OPCIONAL: obtener acciones dinámicas desde BD
    public function actions(): JsonResponse
    {
        $actions = \App\Models\ActivityLog::select('action')
            ->distinct()
            ->pluck('action');

        return response()->json($actions);
    }



    /**
     * 🔹 Cerrar sesión específica
     * Elimina el token de Sanctum y marca logout_at
     */
    public function cerrarSesion($id): JsonResponse
    {
        $session = DB::table('user_sessions')->where('id', $id)->first();

        if (!$session) {
            return response()->json(['message' => 'Sesión no encontrada'], 404);
        }

        // 🔹 Eliminar token Sanctum asociado
        DB::table('personal_access_tokens')
            ->where('id', $session->token_id)
            ->delete();

        // 🔹 Marcar sesión como cerrada
        DB::table('user_sessions')
            ->where('id', $id)
            ->update(['logout_at' => now()]);

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }
}
