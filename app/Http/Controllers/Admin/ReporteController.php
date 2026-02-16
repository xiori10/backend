<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Preinscripcion;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $query = Preinscripcion::query();

        // 🔹 Filtro por rango de fechas (incluye todo el día final)
        if ($request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('created_at', [
                $request->fecha_inicio . ' 00:00:00',
                $request->fecha_fin . ' 23:59:59'
            ]);
        }

        // 🔹 Filtro por estado
        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        // 🔹 Filtro por carrera (flexible)
        if ($request->escuela) {
            $query->where('escuela_profesional', 'like', '%' . $request->escuela . '%');
        }

        // 🔹 Clonamos la consulta base
        $baseQuery = clone $query;

        // 🔹 Total general (evita división por cero)
        $totalGeneral = $baseQuery->count();
        $totalGeneral = $totalGeneral > 0 ? $totalGeneral : 1;

        return response()->json([

            // ✅ Totales generales (YA FILTRADOS)
            'totales' => [
                'total' => $totalGeneral,
                'pendientes' => (clone $query)->where('estado', 'PENDIENTE')->count(),
                'pagados' => (clone $query)->where('estado', 'PAGADO')->count(),
                'inscritos' => (clone $query)->where('estado', 'INSCRITO')->count(),
                'rechazados' => (clone $query)->where('estado', 'RECHAZADO')->count(),
            ],

            // ✅ Agrupado por estado
            'por_estado' => (clone $query)
                ->select('estado', DB::raw('COUNT(*) as total'))
                ->groupBy('estado')
                ->get(),

            // ✅ Agrupado por escuela con porcentajes y desglose por estado
            'por_escuela' => (clone $query)
                ->select(
                    'escuela_profesional',
                    DB::raw('COUNT(*) as total'),
                    DB::raw("ROUND(COUNT(*) * 100 / $totalGeneral, 2) as porcentaje"),
                    DB::raw("SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes"),
                    DB::raw("SUM(CASE WHEN estado = 'PAGADO' THEN 1 ELSE 0 END) as pagados"),
                    DB::raw("SUM(CASE WHEN estado = 'INSCRITO' THEN 1 ELSE 0 END) as inscritos"),
                    DB::raw("SUM(CASE WHEN estado = 'RECHAZADO' THEN 1 ELSE 0 END) as rechazados")
                )
                ->groupBy('escuela_profesional')
                ->orderByDesc('total')
                ->get(),

            // ✅ Agrupado por mes
            'por_mes' => (clone $query)
                ->select(
                    DB::raw('MONTH(created_at) as mes'),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('mes')
                ->orderBy('mes')
                ->get(),
        ]);
    }
}