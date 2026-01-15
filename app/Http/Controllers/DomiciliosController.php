<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\Producto;
use App\ProductoVendido;
use App\Venta;
use Illuminate\Http\Request;
use App\Http\Controllers\VentasController;
use DB;

use GuzzleHttp\Client;


use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Codedge\Fpdf\Fpdf\Fpdf;

class DomiciliosController extends Controller
{
    public function obtenerDomicilios(){
        return view("ventas.domicilios");
    }


    public function terminarVentaDomicilio(Request $request)
    {
        $venta = new Venta();
       
        $venta->total_pagar =  $request->input('total_pagar');
        $venta->total_dinero =  $request->input('total_dinero');
        $venta->total_fiado =  $request->input('total_fiado');
        $venta->total_vueltos =  $request->input('total_vueltos');
        $imprimir_factura = $request->input("imprimir_factura");
        $venta->fecha_venta = date("Y-m-d");

        $celular_cliente =  $request->input('celular_cliente');
        $nombre_cliente =  $request->input('nombre_cliente');
        $direccion_cliente =  $request->input('direccion_cliente');


        $cliente = DB::connection('mysql')->table('clientes')
        ->where('telefono', $celular_cliente)
        ->first();

        if($cliente == null){
            $venta->id_cliente = DB::connection('mysql')->table('clientes')
            ->insertGetId([
                'telefono' => $celular_cliente,
                'nombre' => $nombre_cliente            
            ]);
        }else{
            $venta->id_cliente = $cliente->id;
        }

        $venta->saveOrFail();

        if($venta->total_fiado > 0){
            $this->guardarFiado($venta->id_cliente, $venta->id,  $venta->total_fiado);
        }

        $idVenta = $venta->id;
        $productos = $request->input('productos');

        $lista_productos = [];
        foreach ($productos as $producto) {
            $productoVendido = new ProductoVendido();
            $productoVendido->fill([
                "id_venta" => $idVenta,
                "descripcion" => $producto["descripcion"],
                "codigo_barras" => $producto["codigo_barras"],
                "precio" => $producto["precio"],
                "cantidad" => $producto["cantidad"],
                "unidad" => $producto["unidad"] == "Kilos" ? "Kg" : ($producto["unidad"] == "Libras" ? "Lb" : "Und")
            ]);

            $productoVendido->saveOrFail();

            $productoActualizado = Producto::where('codigo_barras', $producto["codigo_barras"])->first();
            $productoActualizado->existencia -= $productoVendido->cantidad;
            
            
            DB::connection('mysql')->table('productos')
            ->where('codigo_barras', $producto["codigo_barras"])
            ->update([
                'existencia' => $productoActualizado->existencia
            ]);
            

            $lista_productos[] = [
                "codigo_barras" => $producto["codigo_barras"],
                "existencia" => $productoActualizado->existencia
            ];
        }
        
        $myVariable = $this->ticket($idVenta, $imprimir_factura, $direccion_cliente);

        $id_pedido =  $request->input('id_pedido');
        $this->actualizarCantidadesProductos($lista_productos, $id_pedido);

        return response()->json([
            'status' => 'success',
            'message' => 'Venta terminada',
            'data' => [
                'venta_id' => $idVenta,
                'ticket' => $myVariable,
            ],
        ]);
    }

    public function actualizarCantidadesProductos($lista_productos, $id_pedido){
        if (checkdnsrr('example.com', 'A')) {
            $client = new Client();

            $url = 'https://provisiones-carlosandres.shop/actualizar_final.php';

            $data = [
                "productos" => json_encode($lista_productos),
                "id_pedido" => $id_pedido
            ];

            $response = $client->post($url, [
                'form_params' => $data
            ]);

            $response = $response->getBody();
            $body = json_decode($response, true);
            
            return $body;
        }
    }

    public function ticket($idVenta, $imprimir_factura, $direccion)
    {
        $venta = Venta::findOrFail($idVenta);

        define('EURO',chr(36));
        $pdf = new Fpdf('P','mm',array(80,150));
        $pdf->AddPage();

        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(60,4,"Provisiones Carlos Andres",0,1,'C');
        $pdf->Cell(60,4,"NIT 12435619",0,1,'C');
        $pdf->Cell(60,4,"CRA 15 #13B Bis - 62",0,1,'C');
        $pdf->Cell(60,4,"Brr. Alfonso Lopez",0,1,'C');
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(60,4,'C.I.F.: 01234567A',0,1,'C');
        $pdf->Cell(60,4,'C/ Arturo Soria, 1',0,1,'C');
        $pdf->Cell(60,4,'C.P.: 28028 Madrid (Madrid)',0,1,'C');
        $pdf->Cell(60,4,'999 888 777',0,1,'C');
        $pdf->Cell(60,4,'alfredo@lacodigoteca.com',0,1,'C');
        
        // DATOS FACTURA        
        $pdf->Ln(5);
        $pdf->Cell(60,4,'Fecha: '.date("d/m/Y"),0,1,'');
        $pdf->Cell(60,4,'Metodo de pago: EFECTIVO',0,1,'');
        
        // COLUMNAS
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(30, 10, 'Articulo', 0);
        $pdf->Cell(5, 10, 'Ud',0,0,'R');
        $pdf->Cell(10, 10, 'Precio',0,0,'R');
        $pdf->Cell(15, 10, 'Total',0,0,'R');
        $pdf->Ln(8);
        $pdf->Cell(60,0,'','T');
        $pdf->Ln(0);
        
        // PRODUCTOS
        $pdf->SetFont('Helvetica', '', 7);

        $TOTAL = 0;
        foreach ($venta->productos as $producto) {
            $pdf->MultiCell(30,4,$producto->descripcion,0,'L'); 
            $pdf->Cell(35, -5, $producto->cantidad ,0,0,'R');
            $pdf->Cell(10, -5, EURO.number_format($producto->precio, 0, ',', ' '),0,0,'R');
            $pdf->Cell(15, -5, EURO.number_format(self::redondearAl100($producto->cantidad * $producto->precio), 2, ',', ' '),0,0,'R');
            $pdf->Ln(3);

            $TOTAL += $producto->cantidad * $producto->precio;
        }
        
        // SUMATORIO DE LOS PRODUCTOS Y EL IVA
        $pdf->Ln(6);
        $pdf->Cell(60,0,'','T');
        $pdf->Ln(2);    
        
        $pdf->Cell(25, 10, 'TOTAL', 0);    
        $pdf->Cell(20, 10, '', 0);
        $pdf->Cell(15, 10, EURO.number_format(self::redondearAl100($venta->total_pagar), 2, ',', ' '),0,0,'R');


        // PIE DE PAGINA
        $pdf->Ln(10);
        $pdf->Cell(60,0,'Cliente: '.$venta->cliente->nombre,0,1,'C');
        $pdf->Ln(3);
        $pdf->Cell(60,0,'Gracias por su compra',0,1,'C');
        
        $pdf->Output('F', 'tickets/ticket_venta_'.$idVenta.'.pdf');

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
            $impresora->text("\nDirección de entrega" . "\n");
            $impresora->text($direccion . "\n");
            $impresora->text("\nDetalle de la compra\n");
            $impresora->text("\n===============================\n");
            $total = 0;
            foreach ($venta->productos as $producto) {
                $subtotal = $producto->cantidad * $producto->precio;
                $total += $subtotal;
                $impresora->setJustification(Printer::JUSTIFY_LEFT);
                $impresora->text(sprintf("%.2f %s x %s\n", $producto->cantidad, $producto->unidad,  $producto->descripcion));
                $impresora->setJustification(Printer::JUSTIFY_RIGHT);
                $impresora->text('$' . self::redondearAl100($subtotal) . "\n");
            }
            $impresora->setJustification(Printer::JUSTIFY_CENTER);
            $impresora->text("\n===============================\n");
            $impresora->setJustification(Printer::JUSTIFY_RIGHT);
            $impresora->setEmphasis(true);
            $impresora->setTextSize(3, 3); 
            $impresora->text("SubTotal: $" . self::redondearAl100($total) . "\n");
            $impresora->text("Domicilio: $" . self::redondearAl100(500) . "\n");
            $impresora->text("Total: $" . self::redondearAl100($total+500) . "\n");
            $impresora->text("\n===============================\n");
            $impresora->text("Pago: $" . self::redondearAl100($venta->total_dinero) . "\n");
            $impresora->text("Vueltos: $" . self::redondearAl100($venta->total_vueltos) . "\n");
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

    function redondearAl100($numero) {
        return round($numero / 100) * 100;
    }

}
