<?php

namespace App\Services;

use App\Producto;
use App\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Cifras de ventas y fiados calculadas en SQL (sin traer todas las ventas a PHP).
 * Mismos criterios que VentasController@index:
 *  - ventas: solo las del usuario (caja) indicado y con cliente existente;
 *  - fiado pendiente: lo calcula FiadosService (mismas reglas en todas las pantallas).
 */
class ResumenVentasService
{
    private function ventasDe($idUsuario)
    {
        return Venta::join("clientes", "clientes.id", "ventas.id_cliente")
            ->where("ventas.id_usuario", $idUsuario);
    }

    public function vendidoEnFecha($idUsuario, $fecha)
    {
        return (float) $this->ventasDe($idUsuario)->where("ventas.fecha_venta", $fecha)->sum("ventas.total_pagar");
    }

    public function numeroVentasEnFecha($idUsuario, $fecha)
    {
        return (int) $this->ventasDe($idUsuario)->where("ventas.fecha_venta", $fecha)->count();
    }

    /**
     * @param string $mes formato "Y-m" (fecha_venta se guarda como texto "Y-m-d")
     */
    public function vendidoEnMes($idUsuario, $mes)
    {
        return (float) $this->ventasDe($idUsuario)->where("ventas.fecha_venta", "like", $mes . "-%")->sum("ventas.total_pagar");
    }

    public function ultimasVentas($idUsuario, $limite = 6)
    {
        return $this->ventasDe($idUsuario)
            ->select("ventas.*", "clientes.nombre as cliente")
            ->orderBy("ventas.created_at", "desc")
            ->limit($limite)
            ->get();
    }

    /**
     * Suma de lo fiado pendiente (sin descontar abonos). Con $idUsuario, solo lo fiado en esa caja.
     * Mismas reglas que FiadosService (fiados pendientes con venta y cliente existentes).
     */
    public function totalFiado($idUsuario = null)
    {
        $consulta = app(FiadosService::class)->fiadosPendientes();
        if ($idUsuario !== null) {
            $consulta->where("ventas.id_usuario", $idUsuario);
        }
        return (float) $consulta->sum("fiados.total_fiado");
    }

    /** Abonos pendientes de los clientes que tienen fiados pendientes. */
    public function totalAbonado()
    {
        return app(FiadosService::class)->totales()->abonado;
    }

    public function fiadoPendiente()
    {
        return app(FiadosService::class)->totales()->por_cobrar;
    }

    public function productosEnAlerta()
    {
        return (int) Producto::where("existencia", "<", Producto::UMBRAL_ALERTA)->count();
    }
}
