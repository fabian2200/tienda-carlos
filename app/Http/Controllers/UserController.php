<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Cliente;
use DB;
use App\Venta;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view("usuarios.usuarios_index", ["usuarios" => User::all()]);
    }

    public function deudores()
    {
        $resultado = Cliente::join("fiados", "clientes.id", "=", "fiados.id_cliente")
        ->join("ventas", "ventas.id", "fiados.id_factura")
        ->selectRaw("clientes.*, SUM(fiados.total_fiado) as total_fiado")
        ->groupBy('clientes.id')
        ->get();

        $clientes_deben = [];
        $total_fiado = 0;

        foreach ($resultado as $item) {
            $abonado = Cliente::join("abonos_fiados", "clientes.id", "=", "abonos_fiados.id_cliente")
            ->selectRaw("clientes.id, SUM(abonos_fiados.valor_abonado) as total_abonado")
            ->where("clientes.id", $item->id)
            ->groupBy('clientes.id')
            ->get();

            if(count($abonado) == 0){
                $total_abonado = 0;
            }else{
                $total_abonado = $abonado[0]->total_abonado;
            }

            $item->total_abonado = $total_abonado;
            $item->total_deuda = $item->total_fiado - $item->total_abonado;

            
            if($item->total_deuda > 0){
                $total_fiado += $item->total_deuda;
                array_push($clientes_deben, $item);
            }
        }

        return view("usuarios.usuarios_deudores", ["clientes_deudores" => $clientes_deben, "total_fiado" => $total_fiado]);
    }

    public function abonar(Request $request)
    {
        $id_cliente = $request->input('id_cliente');
        $total_abonar = $request->input('total_abonar');

        $datos = [
            'id_cliente' => $id_cliente,
            'valor_abonado' => $total_abonar,
            'fecha_abono' => date("d-m-Y H:i:s")
        ];

        DB::connection('mysql')->table('abonos_fiados')->insert(
            $datos 
        );

        
        $fiado = Cliente::join("fiados", "clientes.id", "=", "fiados.id_cliente")
        ->join("ventas", "ventas.id", "fiados.id_factura")
        ->selectRaw("clientes.*, SUM(fiados.total_fiado) as total_fiado")
        ->groupBy('clientes.id')
        ->where("clientes.id", $id_cliente)
        ->first();

        $abonado = Cliente::join("abonos_fiados", "clientes.id", "=", "abonos_fiados.id_cliente")
        ->selectRaw("clientes.id, SUM(abonos_fiados.valor_abonado) as total_abonado")
        ->where("clientes.id", $id_cliente)
        ->groupBy('clientes.id')
        ->get();

        if(count($abonado) == 0){
            $total_abonado = 0;
        }else{
            $total_abonado = $abonado[0]->total_abonado;
        }


        $total_deuda = $fiado->total_fiado - $total_abonado;
        
        if($total_deuda < 1){
            DB::connection('mysql')->table('ventas')
            ->where("id_cliente", $id_cliente)
            ->update([
                'total_fiado' => 0,
            ]);

            DB::connection('mysql')->table('fiados')
            ->where("id_cliente", $id_cliente)
            ->delete();

            DB::connection('mysql')->table('abonos_fiados')
            ->where("id_cliente", $id_cliente)
            ->delete();
        }
        
        return redirect('/usuarios-deudores');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("usuarios.usuarios_create");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $usuario = new User($request->input());
        $usuario->password = Hash::make($usuario->password);
        $usuario->saveOrFail();
        return redirect()->route("usuarios.index")->with("mensaje", "Usuario guardado");
    }

    /**
     * Display the specified resource.
     *
     * @param \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $user->password = "";
        return view("usuarios.usuarios_edit", ["usuario" => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $user->fill($request->input());
        $user->password = Hash::make($user->password);
        $user->saveOrFail();
        return redirect()->route("usuarios.index")->with("mensaje", "Usuario actualizado");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route("usuarios.index")->with("mensaje", "Usuario eliminado");
    }

    public function infoDeuda(Request $request){
        $id = $request->input("id_cliente");

        $resultado = Cliente::join("fiados", "clientes.id", "=", "fiados.id_cliente")
        ->join("ventas", "ventas.id", "fiados.id_factura")
        ->selectRaw("clientes.*, SUM(fiados.total_fiado) as total_fiado")
        ->groupBy('clientes.id')
        ->where("clientes.id", $id)
        ->first();

       
        $abonado = Cliente::join("abonos_fiados", "clientes.id", "=", "abonos_fiados.id_cliente")
        ->selectRaw("clientes.id, SUM(abonos_fiados.valor_abonado) as total_abonado")
        ->where("clientes.id", $id)
        ->groupBy('clientes.id')
        ->get();

        if(count($abonado) == 0){
            $total_abonado = 0;
        }else{
            $total_abonado = $abonado[0]->total_abonado;
        }

        $resultado->total_abonado = $total_abonado;
        $resultado->total_deuda = $resultado->total_fiado - $resultado->total_abonado;


        $facturas_deudas =  DB::connection('mysql')->table('ventas')
        ->where("id_cliente", $id)
        ->where("total_fiado", ">", 0)
        ->get();

        foreach ($facturas_deudas as $item) {
            $item->productos = DB::connection('mysql')->table('productos_vendidos')
            ->where("id_venta", $item->id)
            ->get();
        }

        return view("usuarios.info_deuda", ["cliente" => $resultado, "facturas_deudas" => $facturas_deudas]);
    }

    public function ImprimirDeuda(Request $request){

        $id_cliente = $request->input("id_cliente");

        $resultado = Cliente::join("fiados", "clientes.id", "=", "fiados.id_cliente")
        ->selectRaw("clientes.*, SUM(fiados.total_fiado) as total_fiado")
        ->groupBy('clientes.id')
        ->where("clientes.id", $id_cliente)
        ->first();

       
        $abonado = Cliente::join("abonos_fiados", "clientes.id", "=", "abonos_fiados.id_cliente")
        ->selectRaw("clientes.id, SUM(abonos_fiados.valor_abonado) as total_abonado")
        ->where("clientes.id", $id_cliente)
        ->groupBy('clientes.id')
        ->get();

        $total_deuda = 0;
        if(count($abonado) == 0){
            $total_abonado = 0;
        }else{
            $total_abonado = $abonado[0]->total_abonado;
        }

        $total_deuda = $resultado->total_fiado - $total_abonado;

        $facturas_deudas =  DB::connection('mysql')->table('ventas')
        ->where("id_cliente", $id_cliente)
        ->where("total_fiado", ">", 0)
        ->get();

        foreach ($facturas_deudas as $item) {
            $item->productos = DB::connection('mysql')->table('productos_vendidos')
            ->where("id_venta", $item->id)
            ->get();
        }

        $nombreImpresora = env("NOMBRE_IMPRESORA");
        $connector = new WindowsPrintConnector($nombreImpresora);
        $impresora = new Printer($connector);
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->setEmphasis(true);
        $impresora->text("Ticket de Deuda\n");
        $impresora->text("Provisiones Carlos Andres\n");
        $impresora->text("NIT 12435619\n");
        $impresora->text("CRA 15 #13B Bis - 62\n");
        $impresora->text("Brr. Alfonso Lopez\n");
        $impresora->text(date("d/m/Y") . "\n");
        $impresora->setEmphasis(false);
        $impresora->text("Cliente: ");
        $impresora->text($resultado->nombre . "\n");
        $impresora->text("\nDetalle de la deuda\n");
        foreach ($facturas_deudas as $venta) {
            $impresora->setJustification(Printer::JUSTIFY_CENTER);
            $impresora->text("\n===============================\n");
            $impresora->text("\nFactura #".$venta->id."\n");
            $impresora->text("\nFecha Factura #".$venta->fecha_venta."\n");
            $impresora->text("\n");
            $total = 0;
            foreach ($venta->productos as $producto) {
                $subtotal = $producto->cantidad * $producto->precio;
                $total = $total + self::redondearAl100($subtotal);
                $impresora->setJustification(Printer::JUSTIFY_LEFT);
                $impresora->text(sprintf("%.2f %s x %s\n", $producto->cantidad, $producto->unidad,  $producto->descripcion));
                $impresora->setJustification(Printer::JUSTIFY_RIGHT);
                $impresora->text('$' . self::redondearAl100($subtotal) . "\n");
            }
            $impresora->setJustification(Printer::JUSTIFY_RIGHT);
            $impresora->setEmphasis(true);
            $impresora->text("Total Factura: $" . self::redondearAl100($total) . "\n");
            $impresora->text("Total fiado Factura: $" . self::redondearAl100($venta->total_fiado) . "\n");
        }
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->text("\n======== Deuda Total ============\n");
        $impresora->setJustification(Printer::JUSTIFY_LEFT);
        $impresora->setTextSize(3, 3);
        $impresora->text(sprintf("Total fiado $ %.2f\n", self::redondearAl100($resultado->total_fiado)));
        $impresora->text(sprintf("Total Abonado $ %.2f\n", self::redondearAl100($total_abonado)));
        $impresora->text(sprintf("Total Deuda Restante $ %.2f\n", self::redondearAl100($total_deuda)));
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->setTextSize(1, 1);
        $impresora->text("\nDetalle de Deuda\n");
        $impresora->text("\nVentSOFT By Ing. Fabian Quintero\n");
        $impresora->close();
        return response()->json(["mensaje" => "Ticket de deuda impreso correctamente!"]);
    }

    function redondearAl100($numero) {
        return round($numero / 100) * 100;
    }
}

//DELETE FROM ventas WHERE id NOT IN (SELECT id_venta FROM productos_vendidos GROUP BY id_venta)