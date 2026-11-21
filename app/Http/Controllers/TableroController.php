<?php

namespace App\Http\Controllers;

use App\Services\TableroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tablero con las métricas del negocio (todas las cajas). Solo para el administrador.
 */
class TableroController extends Controller
{
    public function index(Request $request, TableroService $tablero)
    {
        abort_unless(Auth::user()->email == "admin", 403, "El tablero es solo para el administrador.");

        return view("tablero.tablero", [
            "d" => $tablero->datos($request->query("periodo", "30d")),
            "periodos" => TableroService::PERIODOS,
        ]);
    }
}
