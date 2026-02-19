<?php

namespace App\Services;

use App\Models\LoginAttempt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

class LoginAttemptService
{
    /**
     * 🔹 Registrar un intento de login
     *
     * @param string $email Correo del usuario
     * @param bool $successful Si fue exitoso o no
     */
    public static function register(string $email, bool $successful): void
    {
        LoginAttempt::create([
            'email' => $email,
            'ip_address' => Request::ip(),
            'successful' => $successful,
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * 🔹 Contar intentos fallidos recientes de un usuario/IP
     *
     * @param string $email Correo del usuario
     * @param string|null $ip IP específica (por defecto la actual)
     * @return int Número de intentos fallidos
     */
    public static function failedAttempts(string $email, ?string $ip = null): int
    {
        $ip = $ip ?? Request::ip();

        // 🔹 Leer tiempo de bloqueo dinámicamente desde configuraciones
        $blockMinutes = (int) DB::table('configuraciones')->value('tiempo_sesion') ?? 15;

        $timeLimit = Carbon::now()->subMinutes($blockMinutes);

        return LoginAttempt::where('email', $email)
            ->where('ip_address', $ip)
            ->where('successful', false)
            ->where('created_at', '>=', $timeLimit)
            ->count();
    }

    /**
     * 🔹 Comprobar si un usuario/IP está bloqueado
     *
     * @param string $email Correo del usuario
     * @param string|null $ip IP específica (opcional)
     * @return bool True si bloqueado, false si no
     */
    public static function isBlocked(string $email, ?string $ip = null): bool
    {
        // 🔹 Leer máximo de intentos fallidos dinámicamente desde configuraciones
        $maxAttempts = (int) DB::table('configuraciones')->value('max_intentos_login') ?? 5;

        return self::failedAttempts($email, $ip) >= $maxAttempts;
    }
}
