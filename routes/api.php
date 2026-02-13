<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\PreinscripcionController;
use App\Http\Controllers\UbigeoController;
use App\Http\Controllers\ColegioController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\Auth\AdminAuthController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/

Route::prefix('preinscripciones')->group(function () {
    Route::post('/verificar-dni', [PreinscripcionController::class, 'verificarDNI'])->middleware('throttle:20,1');
    Route::post('/consultar', [PreinscripcionController::class, 'consultarParaModificar']);
    Route::post('/', [PreinscripcionController::class, 'store']);
    Route::get('/{numeroDocumento}', [PreinscripcionController::class, 'show']);
    Route::put('/{numeroDocumento}', [PreinscripcionController::class, 'update']);
    Route::get('/estadisticas/general', [PreinscripcionController::class, 'estadisticas']);
    Route::get('/{numeroDocumento}/ficha', [PreinscripcionController::class, 'imprimirFicha']);
});

// Documentos PDF
Route::get('/documentos/{archivo}', function ($archivo) {
    $path = "documentos/$archivo";
    if (!Storage::disk('public')->exists($path)) abort(404, 'Documento no encontrado');
    return response()->download(Storage::disk('public')->path($path));
});

// Colegios
Route::get('/colegios/{departamento}/{provincia}/{distrito}', [ColegioController::class, 'listar']);

// Ubigeos
Route::prefix('ubigeos')->group(function () {
    Route::get('/departamentos', [UbigeoController::class, 'departamentos']);
    Route::get('/provincias', [UbigeoController::class, 'provincias']);
    Route::get('/distritos', [UbigeoController::class, 'distritos']);
});

// Captcha
Route::get('/captcha', [CaptchaController::class, 'generate']);
Route::post('/captcha/validate', [CaptchaController::class, 'validateCaptcha'])->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| RUTAS ADMIN PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| RUTAS ADMIN PROTEGIDAS
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);

        Route::get('/preinscripciones', [PreinscripcionController::class, 'index']);
        Route::delete('/preinscripciones/{numeroDocumento}', [PreinscripcionController::class, 'destroy']);
    });
