<?php

namespace App\Http\Controllers;

use App\Services\ResumenVentasService;
use App\User;

/**
 * Resumen por caja (solo admin desde el menú). Antes traía todas las ventas de cada
 * caja a PHP; ahora suma en SQL con los mismos criterios (ResumenVentasService).
 */
class CajasController extends Controller
{
    public function index(ResumenVentasService $resumen)
    {
        $hoy = date("Y-m-d");
        $mes = date("Y-m");

        $cajas = User::orderBy("name")->get();
        foreach ($cajas as $caja) {
            $caja->total_ventas_mensual = $resumen->vendidoEnMes($caja->id, $mes);
            $caja->total_ventas_hoy = $resumen->vendidoEnFecha($caja->id, $hoy);
            $caja->ventas_hoy = $resumen->numeroVentasEnFecha($caja->id, $hoy);
            $caja->total_fiado = $resumen->totalFiado($caja->id);
        }

        $totalFiado = $resumen->totalFiado();
        $totalAbonado = $resumen->totalAbonado();

        return view("cajas.cajas_index", [
            "cajas" => $cajas,
            "totalVendidoMensual" => $cajas->sum("total_ventas_mensual"),
            "totalVendidoHoy" => $cajas->sum("total_ventas_hoy"),
            "totalFiado" => $totalFiado,
            "totalAbonado" => $totalAbonado,
            "totalPorCobrar" => $totalFiado - $totalAbonado,
        ]);
    }
}
