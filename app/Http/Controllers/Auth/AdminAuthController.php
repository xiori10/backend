<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\LoginAttemptService;

class AdminAuthController extends Controller
{
    /**
     * 📌 Login de administrador
     * Valida credenciales, registra intentos, bloquea IP/email tras múltiples intentos fallidos,
     * genera token Sanctum si login exitoso.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 🚫 Verificar si email/IP está bloqueado por intentos fallidos
        if (LoginAttemptService::isBlocked($request->email)) {
            return response()->json([
                'message' => 'Demasiados intentos fallidos. Intente nuevamente más tarde.'
            ], 429);
        }

        // Intento de login
        if (!Auth::attempt($credentials)) {
            // Registrar intento fallido
            LoginAttemptService::register($request->email, false);

            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 🚫 Usuario autenticado pero no es admin
        if (!$user->isAdmin()) {
            LoginAttemptService::register($request->email, false);
            Auth::logout();

            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        // ✅ Login exitoso
        LoginAttemptService::register($request->email, true);

        // Generar token Sanctum
        // $token = $user->createToken('admin-token')->plainTextToken;

        $tokenResult = $user->createToken('admin-token');

        $plainTextToken = $tokenResult->plainTextToken;
        $tokenModel = $tokenResult->accessToken;

        // Guardar sesión
        $user->sessions()->create([
            'token_id' => $tokenModel->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_at' => now(),
            'last_activity' => now(),
        ]);

        // return response()->json([
        //     'user' => $user,
        //     'token' => $token,
        // ]);

        return response()->json([
            'user' => $user,
            'token' => $plainTextToken,
        ]);
    }

    /**
     * 📌 Obtener información del usuario admin logueado
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    /**
     * 📌 Logout del usuario admin
     * Elimina el token actual de Sanctum para cerrar sesión.
     */
    // public function logout(Request $request)
    // {
    //     $request->user()->currentAccessToken()->delete();

    //     return response()->json([
    //         'message' => 'Sesión cerrada'
    //     ]);
    // }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            // Actualizar logout_at en user_sessions
            \App\Models\UserSession::where('token_id', $token->id)
                ->update(['logout_at' => now()]);

            // Eliminar token Sanctum
            $token->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada'
        ]);
    }
}
