<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Preinscripcion;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'total' => Preinscripcion::count(),

            'pendientes' => Preinscripcion::estado('PENDIENTE')->count(),
            'inscritos' => Preinscripcion::estado('INSCRITO')->count(),
            'rechazados' => Preinscripcion::estado('RECHAZADO')->count(),

            'recientes' => Preinscripcion::recientes()->count(),

            'por_escuela' => Preinscripcion::select('escuela_profesional', DB::raw('count(*) as total'))
                ->groupBy('escuela_profesional')
                ->get(),

            'usuarios' => User::count(),

            'por_mes' => Preinscripcion::select(
                    DB::raw('MONTH(created_at) as mes'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('mes')
                ->orderBy('mes')
                ->get(),

            'actividad_reciente' => Preinscripcion::latest()
                ->limit(5)
                ->get(['id', 'escuela_profesional', 'estado', 'created_at']),
        ]);
    }
}