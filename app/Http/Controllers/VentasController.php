<?php

namespace App\Http\Controllers;

use App\Services\FiadosService;
use App\Services\ResumenVentasService;
use App\Services\TicketService;
use App\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Codedge\Fpdf\Fpdf\Fpdf;
use App\Cliente;
use Illuminate\Support\Facades\Auth;

class VentasController extends Controller
{

    public function ticket($idVenta, $imprimir_factura)
    {
        $venta = Venta::findOrFail($idVenta);

        define('EURO',chr(36));

        if($imprimir_factura == "si"){
            $impresora = TicketService::impresora();
            TicketService::encabezado($impresora, "Ticket de venta", $venta->created_at);
            $impresora->text("Cliente: ");
            $impresora->text($venta->cliente->nombre . "\n");
            $impresora->text("Vendido por: " . $venta->usuario->name . "\n");
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
            TicketService::pie($impresora);
            $impresora->feed(10);
            $impresora->pulse();
            $impresora->close();
        }

        return true;
    }

    public function ImprimirTicket(Request $request){

        $idVenta = $request->input("id_venta");
        
        $venta = Venta::findOrFail($idVenta);

        $impresora = TicketService::impresora();
        TicketService::encabezado($impresora, "Ticket de venta", $venta->created_at);
        $impresora->text("Cliente: ");
        $impresora->text($venta->cliente->nombre . "\n");
        $impresora->text("Vendido por: " . $venta->usuario->name . "\n");
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
        TicketService::pie($impresora);
        $impresora->feed(10);
        $impresora->pulse();
        $impresora->close();
        return response()->json(["mensaje" => "Ticket de venta impreso correctamente!"]);
    }

    function redondearAl100($numero) {
        return round($numero / 100) * 100;
    }

    /**
     * Ventas de la caja que inició sesión. Une el antiguo "Ventas" y "Ventas por fecha":
     * filtros opcionales ?fecha1=Y-m-d&fecha2=Y-m-d&cliente=id, 50 por página.
     * Las cifras se calculan en SQL con ResumenVentasService (mismos resultados que antes).
     */
    public function index(Request $request, ResumenVentasService $resumen)
    {
        $idUsuario = Auth::id();
        $esFecha = function ($valor) { return is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor); };

        $fecha1 = $esFecha($request->input("fecha1")) ? $request->input("fecha1") : null;
        $fecha2 = $esFecha($request->input("fecha2")) ? $request->input("fecha2") : null;
        if ($fecha1 && !$fecha2) { $fecha2 = $fecha1; }
        if ($fecha2 && !$fecha1) { $fecha1 = $fecha2; }
        if ($fecha1 && $fecha1 > $fecha2) { [$fecha1, $fecha2] = [$fecha2, $fecha1]; }
        $idCliente = (int) $request->input("cliente") ?: null;

        $consulta = Venta::join("clientes", "clientes.id", "ventas.id_cliente")
            ->where("ventas.id_usuario", $idUsuario);
        if ($fecha1) {
            $consulta->whereBetween("ventas.fecha_venta", [$fecha1, $fecha2]);
        }
        if ($idCliente) {
            $consulta->where("ventas.id_cliente", $idCliente);
        }

        $filtrando = $fecha1 || $idCliente;
        $totalFiltro = $filtrando ? (float) (clone $consulta)->sum("ventas.total_pagar") : null;

        $ventas = $consulta->select("ventas.*", "clientes.nombre as cliente")
            ->orderBy("ventas.created_at", "DESC")
            ->paginate(50)
            ->appends($request->only(["fecha1", "fecha2", "cliente"]));

        return view("ventas.ventas_index", [
            "ventas" => $ventas,
            "totalVendidoHoy" => $resumen->vendidoEnFecha($idUsuario, date("Y-m-d")),
            "ventasHoy" => $resumen->numeroVentasEnFecha($idUsuario, date("Y-m-d")),
            "totalVendido" => $resumen->vendidoEnMes($idUsuario, date("Y-m")),
            "totalFiado" => $resumen->fiadoPendiente(),
            "filtrando" => $filtrando,
            "totalFiltro" => $totalFiltro,
            "fecha1" => $fecha1,
            "fecha2" => $fecha2,
            "idCliente" => $idCliente,
            "clientes" => Cliente::orderBy("nombre")->get(["id", "nombre"]),
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
     * Detalle de una venta con aspecto de factura (encabezado desde la tabla `negocio`).
     */
    public function show(Venta $venta)
    {
        return view("ventas.ventas_show", $this->datosFactura($venta));
    }

    /**
     * La misma factura en PDF.
     */
    public function pdf(Venta $venta)
    {
        $pdf = \PDF::loadView("ventas.ventas_pdf", $this->datosFactura($venta))->setPaper("letter");
        // Solo las letras usadas de la fuente (si no, cada PDF lleva la fuente completa: ~880 KB)
        $pdf->getDomPDF()->set_option("enable_font_subsetting", true);
        return $pdf->download("venta-" . $venta->id . ".pdf");
    }

    /**
     * Subtotal: suma de cada línea redondeada a 100, igual que el ticket impreso.
     */
    private function datosFactura(Venta $venta)
    {
        $subtotal = 0;
        foreach ($venta->productos as $producto) {
            $producto->subtotal = self::redondearAl100($producto->cantidad * $producto->precio);
            $subtotal += $producto->subtotal;
        }
        $domicilio = (float) $venta->precio_domi;

        return [
            "venta" => $venta,
            "subtotal" => $subtotal,
            "domicilio" => $domicilio,
            "total" => self::redondearAl100($subtotal + $domicilio),
        ];
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
        // La venta y su fiado se borran juntos; si el cliente ya había abonado
        // lo suficiente, queda a paz y salvo (FiadosService::quitarFiadoDeVenta)
        $deMas = DB::transaction(function () use ($venta) {
            $deMas = app(FiadosService::class)->quitarFiadoDeVenta($venta->id);
            $venta->delete();
            return $deMas;
        });

        $mensaje = "Venta eliminada";
        if ($deMas >= FiadosService::TOLERANCIA) {
            $mensaje .= ". El cliente había abonado " . dinero($deMas) . " más de lo que ahora debe; quedó a paz y salvo.";
        }
        return redirect()->route("ventas.index")->with("mensaje", $mensaje);
    }

    /**
     * Ruta antigua "Ventas por fecha": ahora es un filtro del listado de ventas.
     */
    public function ventasPorFecha(Request $request)
    {
        return redirect()->route("ventas.index", $request->only(["fecha1", "fecha2"]));
    }
}
