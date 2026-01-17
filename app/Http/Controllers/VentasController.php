<?php

namespace App\Http\Controllers;

use App\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Codedge\Fpdf\Fpdf\Fpdf;
use App\Cliente;

class VentasController extends Controller
{

    public function ticket($idVenta, $imprimir_factura)
    {
        $venta = Venta::findOrFail($idVenta);

        define('EURO',chr(36));

        if($imprimir_factura == "si"){
            $nombreImpresora = env("NOMBRE_IMPRESORA");
            $connector = new WindowsPrintConnector($nombreImpresora);
            $impresora = new Printer($connector);
            $impresora->setJustification(Printer::JUSTIFY_CENTER);
            $impresora->setEmphasis(true);
            $impresora->text("Ticket de venta\n");
            $impresora->text("Provisiones Carlos Andres\n");
            $impresora->text("NIT 12435619\n");
            $impresora->text("CRA 15 #13B Bis - 62\n");
            $impresora->text("Brr. Alfonso Lopez\n");
            $impresora->text($venta->created_at . "\n");
            $impresora->setEmphasis(false);
            $impresora->text("Cliente: ");
            $impresora->text($venta->cliente->nombre . "\n");
            $impresora->text("\nDetalle de la compra\n");
            $impresora->text("\n===============================\n");
            $total = 0;
            $numero_productos = 0;
            foreach ($venta->productos as $producto) {
                $subtotal = $producto->cantidad * $producto->precio;
                $total = $total + self::redondearAl100($subtotal);
                $impresora->setJustification(Printer::JUSTIFY_LEFT);
                $impresora->text(sprintf("%.2f %s x %s\n", $producto->cantidad, $producto->unidad,  $producto->descripcion));
                $impresora->setJustification(Printer::JUSTIFY_RIGHT);
                $impresora->text('$' . self::redondearAl100($subtotal) . "\n");
                $numero_productos++;
            }
            $impresora->setJustification(Printer::JUSTIFY_CENTER);
            $impresora->text("\n===============================\n");
            $impresora->setJustification(Printer::JUSTIFY_RIGHT);
            $impresora->setTextSize(1, 1); 
            $impresora->text("\nCantidad de productos: " . $numero_productos . "\n");
            $impresora->setJustification(Printer::JUSTIFY_CENTER);
            $impresora->text("\n===============================\n");
            $impresora->setJustification(Printer::JUSTIFY_RIGHT);
            $impresora->setEmphasis(true);
            $impresora->setTextSize(3, 3); 
            $impresora->text("Total: $" . self::redondearAl100($total) . "\n");
            $impresora->setJustification(Printer::JUSTIFY_CENTER);
            $impresora->setTextSize(1, 1);
            $impresora->text("Gracias por su compra\n");
            $impresora->text("\nVentSOFT By Ing. Fabian Quintero\n");
            $impresora->feed(10);
            $impresora->pulse();
            $impresora->close();
        }

        return true;
    }

    public function ImprimirTicket(Request $request){

        $idVenta = $request->input("id_venta");
        
        $venta = Venta::findOrFail($idVenta);

        $nombreImpresora = env("NOMBRE_IMPRESORA");
        $connector = new WindowsPrintConnector($nombreImpresora);
        $impresora = new Printer($connector);
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->setEmphasis(true);
        $impresora->text("Ticket de venta\n");
        $impresora->text("Provisiones Carlos Andres\n");
        $impresora->text("NIT 12435619\n");
        $impresora->text("CRA 15 #13B Bis - 62\n");
        $impresora->text("Brr. Alfonso Lopez\n");
        $impresora->text($venta->created_at . "\n");
        $impresora->setEmphasis(false);
        $impresora->text("Cliente: ");
        $impresora->text($venta->cliente->nombre . "\n");
        $impresora->text("\nDetalle de la compra\n");
        $impresora->text("\n===============================\n");
        $total = 0;
        $numero_productos = 0;
        foreach ($venta->productos as $producto) {
            $subtotal = $producto->cantidad * $producto->precio;
            $total = $total + self::redondearAl100($subtotal);
            $impresora->setJustification(Printer::JUSTIFY_LEFT);
            $impresora->text(sprintf("%.2f %s x %s\n", $producto->cantidad, $producto->unidad,  $producto->descripcion));
            $impresora->setJustification(Printer::JUSTIFY_RIGHT);
            $impresora->text('$' . self::redondearAl100($subtotal) . "\n");
            $numero_productos++;
        }
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->text("\n===============================\n");
        $impresora->setJustification(Printer::JUSTIFY_RIGHT);
        $impresora->setTextSize(1, 1); 
        $impresora->text("\nCantidad de productos: " . $numero_productos . "\n");
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->text("\n===============================\n");
        $impresora->setJustification(Printer::JUSTIFY_RIGHT);
        $impresora->setEmphasis(true);
        $impresora->setTextSize(3, 3);
        if($venta->precio_domi > 0){
            $impresora->text("Subtotal: $" . self::redondearAl100($total) . "\n");
            $impresora->text("Domicilio: $" . $venta->precio_domi . "\n");
            $impresora->text("Total: $" . self::redondearAl100($total + $venta->precio_domi) . "\n");
        }else{
            $impresora->text("Total: $" . self::redondearAl100($total) . "\n");
        }
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->setTextSize(1, 1);
        $impresora->text("Gracias por su compra\n");
        $impresora->text("\nVentSOFT By Ing. Fabian Quintero\n");
        $impresora->feed(10);
        $impresora->pulse();
        $impresora->close();
        return response()->json(["mensaje" => "Ticket de venta impreso correctamente!"]);
    }

    function redondearAl100($numero) {
        return round($numero / 100) * 100;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {        
        $ventasConTotales = Venta::join("clientes", "clientes.id", "ventas.id_cliente")
            ->select("ventas.*", "clientes.nombre as cliente")
            ->orderBy("ventas.created_at", "DESC")
            ->get();

        
        $totalVendido = 0;
        
        $mes_actual = date('m');
        $anio_actual = date('Y');
        foreach ($ventasConTotales as $item) {
            $mes_factura = explode("-", $item->fecha_venta)[1];
            $anio_factura = explode("-", $item->fecha_venta)[0];
            if($mes_actual == $mes_factura && $anio_actual == $anio_factura){
                $totalVendido += $item->total_pagar;
            }
        }

        $fiado = 0;
        $abonado = 0;

        $resultado = Cliente::join("fiados", "clientes.id", "=", "fiados.id_cliente")
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
      
        $totalFiado = $fiado - $abonado;


        $hoy = date("Y-m-d");
        $totalVendidoHoy = Venta::join("clientes", "clientes.id", "ventas.id_cliente")
        ->where("ventas.fecha_venta", $hoy)
        ->sum("ventas.total_pagar");

        $primeros100 = Venta::join("clientes", "clientes.id", "ventas.id_cliente")
            ->select("ventas.*", "clientes.nombre as cliente")
            ->orderBy("ventas.created_at", "DESC")
            ->limit(100)
            ->get();
            
        return view("ventas.ventas_index", [
            "ventas" => $primeros100, 
            "totalVendido" => $totalVendido,
            "totalFiado" => $totalFiado,
            "totalVendidoHoy" => $totalVendidoHoy
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Venta $venta
     * @return \Illuminate\Http\Response
     */
    public function show(Venta $venta)
    {
        $total = 0;
        foreach ($venta->productos as $producto) {
            $total += $producto->cantidad * $producto->precio;
        }
        return view("ventas.ventas_show", [
            "venta" => $venta,
            "total" => $total,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Venta $venta
     * @return \Illuminate\Http\Response
     */
    public function edit(Venta $venta)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Venta $venta
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Venta $venta)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Venta $venta
     * @return \Illuminate\Http\Response
     */
    public function destroy(Venta $venta)
    {
        $venta->delete();
        return redirect()->route("ventas.index")
            ->with("mensaje", "Venta eliminada");
    }

    public function ventasPorFecha(Request $request)
    {

        $fecha1 = $request->input("fecha1");
        $fecha2 = $request->input("fecha2");


        $ventasConTotales = Venta::join("clientes", "clientes.id", "ventas.id_cliente")
        ->select("ventas.*", "clientes.nombre as cliente")
        ->whereBetween("ventas.fecha_venta", [$fecha1, $fecha2])
        ->orderBy("ventas.fecha_venta", "ASC")
        ->get();

    
        $totalVendido = 0;
    
       
        
        foreach ($ventasConTotales as $item) {
            $mes_factura = explode("-", $item->fecha_venta)[1];
            $anio_factura = explode("-", $item->fecha_venta)[0];
            $totalVendido += $item->total_pagar;
        }
            
        return view("ventas.ventas_mes", [
            "ventas" => $ventasConTotales, 
            "totalVendido" => $totalVendido,
        ]);
    }
}
