<?php

namespace App\Services;

use App\Producto;
use App\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Cifras de ventas y fiados calculadas en SQL (sin traer todas las ventas a PHP).
 * Mismos criterios que VentasController@index:
 *  - ventas: solo las del usuario (caja) indicado y con cliente existente;
 *  - fiado pendiente: de todos los clientes, fiados con venta y cliente existentes
 *    menos los abonos de esos mismos clientes.
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

    // Fiados con cliente y venta existentes (mismo criterio que las pantallas anteriores)
    private function fiados($consulta)
    {
        return $consulta->from("fiados")
            ->join("clientes", "clientes.id", "=", "fiados.id_cliente")
            ->join("ventas", "ventas.id", "=", "fiados.id_factura");
    }

    /**
     * Suma de lo fiado (sin descontar abonos). Con $idUsuario, solo lo fiado en esa caja.
     */
    public function totalFiado($idUsuario = null)
    {
        $consulta = $this->fiados(DB::query());
        if ($idUsuario !== null) {
            $consulta->where("ventas.id_usuario", $idUsuario);
        }
        return (float) $consulta->sum("fiados.total_fiado");
    }

    /**
     * Abonos de los clientes que tienen fiados.
     */
    public function totalAbonado()
    {
        return (float) DB::table("abonos_fiados")
            ->whereIn("id_cliente", function ($consulta) {
                $this->fiados($consulta)->select("fiados.id_cliente");
            })
            ->sum("valor_abonado");
    }

    public function fiadoPendiente()
    {
        return $this->totalFiado() - $this->totalAbonado();
    }

    public function productosEnAlerta()
    {
        return (int) Producto::where("existencia", "<", Producto::UMBRAL_ALERTA)->count();
    }
}
