<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\LoginAttemptService;

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
