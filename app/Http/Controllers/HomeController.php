<?php

namespace App\Http\Controllers;

use App\Services\ResumenVentasService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Inicio: cifras del día de la caja que inició sesión, accesos y últimas ventas.
     */
    public function index(ResumenVentasService $resumen)
    {
        $idUsuario = Auth::id();
        $hoy = date("Y-m-d");
        $hora = (int) date("G");

        return view('home', [
            "saludo" => $hora < 12 ? "Buenos días" : ($hora < 19 ? "Buenas tardes" : "Buenas noches"),
            "fechaLarga" => now()->locale("es")->isoFormat("dddd D [de] MMMM [de] YYYY"),
            "vendidoHoy" => $resumen->vendidoEnFecha($idUsuario, $hoy),
            "ventasHoy" => $resumen->numeroVentasEnFecha($idUsuario, $hoy),
            "vendidoMes" => $resumen->vendidoEnMes($idUsuario, date("Y-m")),
            "fiadoPendiente" => $resumen->fiadoPendiente(),
            "productosEnAlerta" => $resumen->productosEnAlerta(),
            "ultimasVentas" => $resumen->ultimasVentas($idUsuario, 6),
            "esAdmin" => Auth::user()->email == "admin",
        ]);
    }
}
