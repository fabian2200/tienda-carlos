<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Venta;
use App\User;
use App\Cliente;

class CajasController extends Controller
{
    public function index()
    {
        $cajas = User::all();

        $hoy = date("Y-m-d");
        $mes_actual = date('m');
        $anio_actual = date('Y');

        $totalVendidoMensual = 0;
        $totalVendidoHoy = 0;
        $totalFiado = 0;


        foreach ($cajas as $caja) {
            $ventasConTotales = Venta::join("clientes", "clientes.id", "ventas.id_cliente")
                ->select("ventas.*", "clientes.nombre as cliente")
                ->orderBy("ventas.created_at", "DESC")
                ->where("ventas.id_usuario", $caja->id)
                ->get();

            
            $caja->total_ventas_mensual = 0;
            
            foreach ($ventasConTotales as $item) {
                $mes_factura = explode("-", $item->fecha_venta)[1];
                $anio_factura = explode("-", $item->fecha_venta)[0];
                if($mes_actual == $mes_factura && $anio_actual == $anio_factura){
                    $caja->total_ventas_mensual += $item->total_pagar;
                }
            }

            $fiado = 0;
            $abonado = 0;

            $resultado = Cliente::join("fiados", "clientes.id", "=", "fiados.id_cliente")
            ->join("ventas", "ventas.id", "=", "fiados.id_factura")
            ->where("ventas.id_usuario", $caja->id)
            ->selectRaw("clientes.*, SUM(fiados.total_fiado) as total_fiado")
            ->groupBy('clientes.id')
            ->get();


            foreach ($resultado as $item) {
                $abonado_cliente = Cliente::join("abonos_fiados", "clientes.id", "=", "abonos_fiados.id_cliente")
                ->selectRaw("clientes.id, SUM(abonos_fiados.valor_abonado) as total_abonado")
                ->where("clientes.id", $item->id)
                ->groupBy('clientes.id')
                ->get();

                if(count($abonado_cliente) == 0){
                    $total_abonado = 0;
                }else{
                    $total_abonado = (double) $abonado_cliente[0]->total_abonado;
                }

                $abonado = $abonado + $total_abonado;
                $fiado = $fiado + $item->total_fiado;

            }
        
            $caja->total_fiado = $fiado - $abonado;

            
            $caja->total_ventas_hoy = Venta::join("clientes", "clientes.id", "ventas.id_cliente")
            ->where("ventas.fecha_venta", $hoy)
            ->where("ventas.id_usuario", $caja->id)
            ->sum("ventas.total_pagar");

            $totalVendidoMensual += $caja->total_ventas_mensual;
            $totalVendidoHoy += $caja->total_ventas_hoy;
            $totalFiado += $caja->total_fiado;
        }

        return view("cajas.cajas_index", compact("cajas", "totalVendidoMensual", "totalVendidoHoy", "totalFiado"));
    }
}
