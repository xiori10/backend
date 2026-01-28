<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class CaptchaController extends Controller
{
    // 1️⃣ Generar captcha
    public function generate()
    {
        $code = strtoupper(Str::random(6));
        $token = (string) Str::uuid();

        Cache::put(
            'captcha_' . $token,
            $code,
            now()->addMinutes(2) // expira en 2 min
        );

        return response()->json([
            'token' => $token,
            'captcha' => $code
        ]);
    }

    // 2️⃣ Validar captcha
    // public function validateCaptcha(Request $request)
    // {
    //     $request->validate([
    //         'token' => 'required',
    //         'captcha' => 'required'
    //     ]);

    //     $key = 'captcha_' . $request->token;
    //     $realCaptcha = Cache::get($key);

    //     if (!$realCaptcha || strtoupper($request->captcha) !== $realCaptcha) {
    //         return response()->json([
    //             'message' => 'CAPTCHA incorrecto'
    //         ], 422);
    //     }

    //     // eliminar captcha (uso único)
    //     Cache::forget($key);

    //     return response()->json([
    //         'message' => 'CAPTCHA válido'
    //     ]);
    // }


    public function validateCaptcha(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'captcha' => 'required'
        ]);

        $token = $request->token;
        $key = 'captcha_' . $token;
        $attemptKey = 'captcha_attempts_' . $token;

        $realCaptcha = Cache::get($key);
        $attempts = Cache::get($attemptKey, 0);

        if ($attempts >= 5) {
            return response()->json([
                'message' => 'Demasiados intentos. Recargue el CAPTCHA.'
            ], 429);
        }

        if (!$realCaptcha || strtoupper($request->captcha) !== $realCaptcha) {
            Cache::put($attemptKey, $attempts + 1, now()->addMinutes(2));
            return response()->json([
                'message' => 'CAPTCHA incorrecto'
            ], 422);
        }

        // CAPTCHA correcto: eliminar token y contador
        Cache::forget($key);
        Cache::forget($attemptKey);

        return response()->json([
            'message' => 'CAPTCHA válido'
        ]);
    }

}
