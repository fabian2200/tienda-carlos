<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Cliente;
use DB;
use App\Venta;
use App\Services\FiadosService;
use App\Services\TicketService;
use InvalidArgumentException;
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
        $usuarios = User::select("users.*")
            ->selectRaw("(SELECT COUNT(*) FROM ventas WHERE ventas.id_usuario = users.id) as numero_ventas")
            ->orderBy("name")
            ->get();
        return view("usuarios.usuarios_index", ["usuarios" => $usuarios]);
    }

    public function deudores(FiadosService $fiados)
    {
        $totales = $fiados->totales();
        return view("usuarios.usuarios_deudores", [
            "clientes_deudores" => $fiados->deudores(),
            "total_fiado" => $totales->fiado,
            "total_abonado_clientes" => $totales->abonado,
            "total_por_cobrar" => $totales->por_cobrar,
        ]);
    }

    public function abonar(Request $request, FiadosService $fiados)
    {
        $request->validate([
            "id_cliente" => "required|integer",
            "total_abonar" => "required|numeric|gt:0",
        ], [], ["total_abonar" => "valor del abono"]);

        try {
            $resultado = $fiados->registrarAbono($request->input("id_cliente"), $request->input("total_abonar"));
        } catch (InvalidArgumentException $e) {
            return response()->json(["mensaje" => $e->getMessage()], 422);
        }

        return response()->json([
            "mensaje" => $resultado->saldada ? "Abono registrado. El cliente quedó a paz y salvo." : "Abono realizado correctamente",
            "deuda_restante" => $resultado->deuda_restante,
            "saldada" => $resultado->saldada,
        ]);
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
        $datos = $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|string|max:255|unique:users,email",
            "password" => "required|string|min:4",
        ], [], ["name" => "nombre", "email" => "usuario"]);

        $usuario = new User($datos);
        $usuario->password = Hash::make($datos["password"]);
        $usuario->saveOrFail();
        return redirect()->route("usuarios.index")->with("mensaje", "Usuario creado: " . $usuario->name)->with("tipo", "success");
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
        $datos = $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|string|max:255|unique:users,email," . $user->id,
            "password" => "nullable|string|min:4",
        ], [], ["name" => "nombre", "email" => "usuario"]);

        $user->name = $datos["name"];
        $user->email = $datos["email"];
        // Contraseña vacía = se conserva la actual (antes había que escribirla siempre)
        if (!empty($datos["password"])) {
            $user->password = Hash::make($datos["password"]);
        }
        $user->saveOrFail();
        return redirect()->route("usuarios.index")->with("mensaje", "Usuario actualizado: " . $user->name)->with("tipo", "success");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        // Evita quedarse sin acceso: no se puede eliminar el usuario con el que se inició sesión
        if (Auth::id() === $user->id) {
            return redirect()->route("usuarios.index")
                ->with("mensaje", "No puedes eliminar el usuario con el que iniciaste sesión.")
                ->with("tipo", "danger");
        }
        $user->delete();
        return redirect()->route("usuarios.index")->with("mensaje", "Usuario eliminado")->with("tipo", "success");
    }

    /** Cliente con su fiado, abonado, deuda y las ventas con fiado pendiente. */
    private function datosDeuda(FiadosService $fiados, $idCliente)
    {
        $cliente = Cliente::findOrFail($idCliente);
        $estado = $fiados->estadoCliente($idCliente);
        $cliente->total_fiado = $estado->fiado;
        $cliente->total_abonado = $estado->abonado;
        $cliente->total_deuda = $estado->deuda;

        $facturas = $fiados->facturasPendientes($idCliente);
        foreach ($facturas as $item) {
            $item->productos = DB::connection('mysql')->table('productos_vendidos')
            ->where("id_venta", $item->id)
            ->get();
        }
        return [$cliente, $facturas];
    }

    public function infoDeuda(Request $request, FiadosService $fiados){
        [$resultado, $facturas_deudas] = $this->datosDeuda($fiados, $request->input("id_cliente"));
        return view("usuarios.info_deuda", ["cliente" => $resultado, "facturas_deudas" => $facturas_deudas]);
    }

    public function ImprimirDeuda(Request $request, FiadosService $fiados){
        [$resultado, $facturas_deudas] = $this->datosDeuda($fiados, $request->input("id_cliente"));
        $total_abonado = $resultado->total_abonado;
        $total_deuda = $resultado->total_deuda;

        $impresora = TicketService::impresora();
        TicketService::encabezado($impresora, "Ticket de Deuda", date("d/m/Y"));
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
        TicketService::pie($impresora, false);
        $impresora->close();
        return response()->json(["mensaje" => "Ticket de deuda impreso correctamente!"]);
    }

    function redondearAl100($numero) {
        return round($numero / 100) * 100;
    }
}

//DELETE FROM ventas WHERE id NOT IN (SELECT id_venta FROM productos_vendidos GROUP BY id_venta)