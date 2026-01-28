<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PreinscripcionController;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\UbigeoController;

use App\Http\Controllers\ColegioController;

use App\Http\Controllers\CaptchaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas - No requieren autenticación
Route::prefix('preinscripciones')->group(function () {

    // Verificar DNI antes de inscribirse
    Route::post('/verificar-dni', [PreinscripcionController::class, 'verificarDNI'])->middleware('throttle:20,1');

    // Consultar para modificar (requiere código de seguridad)
    Route::post('/consultar', [PreinscripcionController::class, 'consultarParaModificar']);

    // Crear nueva pre-inscripción
    Route::post('/', [PreinscripcionController::class, 'store']);

    // Obtener pre-inscripción por DNI
    Route::get('/{numeroDocumento}', [PreinscripcionController::class, 'show']);

    // Actualizar pre-inscripción (requiere código de seguridad en el body)
    Route::put('/{numeroDocumento}', [PreinscripcionController::class, 'update']);

    // Obtener estadísticas (podría protegerse con autenticación en producción)
    Route::get('/estadisticas/general', [PreinscripcionController::class, 'estadisticas']);

    // Imprimir ficha de inscripción
    Route::get('/{numeroDocumento}/ficha', [PreinscripcionController::class, 'imprimirFicha']);


});


/*
|--------------------------------------------------------------------------
| Documentos PDF Institucionales
|--------------------------------------------------------------------------
*/

// Route::get('/documentos/{archivo}', function ($archivo) {

//     $path = "documentos/$archivo";

//     if (!Storage::disk('public')->exists($path)) {
//         return response()->json([
//             'message' => 'Documento no encontrado'
//         ], 404);
//     }

//     return Storage::disk('public')->download($path);
//     // return Storage::disk('public')->path($path);

// });




Route::get('/documentos/{archivo}', function ($archivo) {

    $path = "documentos/$archivo";

    if (!Storage::disk('public')->exists($path)) {
        abort(404, 'Documento no encontrado');
    }

    return response()->download(
        Storage::disk('public')->path($path)
    );
});

Route::get('/colegios/{departamento}/{provincia}/{distrito}', [ColegioController::class, 'listar']);
// ruta de archivos ubigeo
Route::prefix('ubigeos')->group(function () {
    Route::get('/departamentos', [UbigeoController::class, 'departamentos']);
    Route::get('/provincias', [UbigeoController::class, 'provincias']);
    Route::get('/distritos', [UbigeoController::class, 'distritos']);

    
});


Route::get('/captcha', [CaptchaController::class, 'generate']);
// Route::post('/captcha/validate', [CaptchaController::class, 'validateCaptcha']);
Route::post('/captcha/validate', [CaptchaController::class, 'validateCaptcha'])
     ->middleware('throttle:10,1'); // máximo 10 solicitudes/minuto por IP




// Rutas administrativas - Requerirían autenticación en producción
Route::middleware(['auth:sanctum'])->group(function () {
    // Listar todas las pre-inscripciones con filtros
    Route::get('/admin/preinscripciones', [PreinscripcionController::class, 'index']);

    // Eliminar pre-inscripción (soft delete)
    Route::delete('/admin/preinscripciones/{numeroDocumento}', [PreinscripcionController::class, 'destroy']);
});
