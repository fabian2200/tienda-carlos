<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\Producto;
use App\ProductoVendido;
use App\Venta;
use Illuminate\Http\Request;
use App\Http\Controllers\VentasController;
use DB;
use App\Http\Controllers\DomiciliosController;
use Illuminate\Support\Facades\Auth;
use App\Services\FiadosService;

class VenderController extends Controller
{
    public function terminarOCancelarVenta(Request $request)
    {
        if ($request->input("accion") == "terminar") {
            return $this->terminarVenta($request);
        } else {
            return $this->cancelarVenta();
        }
    }

    public function terminarVenta(Request $request)
    {
        $imprimir_factura = $request->input("imprimir_factura");
        $productos = $this->obtenerProductos();

        // Venta, fiado, productos y existencias se guardan juntos: si algo falla no queda nada a medias
        $idVenta = DB::transaction(function () use ($request, $productos) {
            $venta = new Venta();
            $venta->id_cliente =  $request->input('id_cliente');
            $venta->total_pagar =  $request->input('total_pagar');
            $venta->total_dinero =  $request->input('total_dinero');
            $venta->total_fiado =  $request->input('total_fiado');
            $venta->total_vueltos =  $request->input('total_vueltos');
            $venta->fecha_venta = date("Y-m-d");
            $venta->id_usuario = Auth::user()->id;
            $venta->saveOrFail();

            app(FiadosService::class)->guardarFiado($venta->id_cliente, $venta->id, $venta->total_fiado);

            foreach ($productos as $producto) {
                $productoVendido = new ProductoVendido();
                $productoVendido->fill([
                    "id_venta" => $venta->id,
                    "descripcion" => $producto->descripcion,
                    "codigo_barras" => $producto->codigo_barras,
                    "precio" => $producto->precio_venta,
                    "cantidad" => $producto->cantidad,
                    "unidad" => $producto->unidad_medida== "Kilos" ? "Kg" : ($producto->unidad_medida == "Libras" ? "Lb" : "Und")
                ]);
                $productoVendido->saveOrFail();

                $productoActualizado = Producto::find($producto->id);
                $productoActualizado->existencia -= $productoVendido->cantidad;
                $productoActualizado->saveOrFail();
            }

            return $venta->id;
        });

        // El ticket se imprime después de guardar: si falla la impresora, la venta ya quedó guardada
        $objeto = new VentasController();
        $myVariable = $objeto->ticket($idVenta, $imprimir_factura);

        //$actualizar = new DomiciliosController();
        //$actualizar->actualizarCantidadesProductos($lista_productos, null);
        
        $this->vaciarProductos();

        return response()->json([
            'status' => 'success',
            'message' => 'Venta terminada',
            'data' => [
                'venta_id' => $idVenta,
                'ticket' => $myVariable,
            ],
        ]);
    }


    private function obtenerProductos()
    {
        $productos = session("productos");
        if (!$productos) {
            $productos = [];
        }
        return $productos;
    }

    private function vaciarProductos()
    {
        $this->guardarProductos(null);
    }

    private function guardarProductos($productos)
    {
        session(["productos" => $productos]);
    }

    public function cancelarVenta()
    {
        $this->vaciarProductos();

        return response()->json([
            'status' => 'success',
            'message' => 'Venta cancelada',
        ]);
    }

    public function quitarProductoDeVenta(Request $request)
    {
        $indice = $request->post("indice");
        $productos = $this->obtenerProductos();
        array_splice($productos, $indice, 1);
        $this->guardarProductos($productos);
        if(count($productos) == 0){
            $this->vaciarProductos();
        }

        return redirect()->route("vender.index");
    }

    public function agregarProductoVenta(Request $request)
    {        
        $codigo = $request->post("codigo");
        $cantidad = $request->post("cantidad");
        $producto = Producto::where("codigo_barras", "=", $codigo)->first();
        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado',
            ]);
        }
        
        $respuesta = $this->agregarProductoACarrito($producto, $cantidad);

        return $respuesta;
    }

    private function agregarProductoACarrito($producto, $cantidad)
    {
        if ($producto->existencia <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay existencias del producto',
            ]);
        }else{

            $productos = $this->obtenerProductos();
            $posibleIndice = $this->buscarIndiceDeProducto($producto->codigo_barras, $productos);
    
            if ($posibleIndice === -1) {
                if ($cantidad <= $producto->existencia) {
                    $producto->cantidad = $cantidad;
                    $producto->precio_total = self::redondearAl100($producto->cantidad * $producto->precio_venta);
                    array_unshift($productos, $producto);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No se pueden agregar más productos de este tipo, se quedarían sin existencia',
                    ]);
                }
            } else {
                if ($productos[$posibleIndice]->cantidad + $cantidad > $producto->existencia) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No se pueden agregar más productos de este tipo, se quedarían sin existencia',
                    ]);
                }
                $productos[$posibleIndice]->cantidad += $cantidad;
                $productos[$posibleIndice]->precio_total =  self::redondearAl100($productos[$posibleIndice]->cantidad * $productos[$posibleIndice]->precio_venta);
            }
            $this->guardarProductos($productos);

            return response()->json([
                'status' => 'success',
                'message' => 'Producto agregado a la venta',
            ]);
        }

    }

    function redondearAl100($numero) {
        return round($numero / 100) * 100;
    }

    private function buscarIndiceDeProducto(string $codigo, array &$productos)
    {
        foreach ($productos as $indice => $producto) {
            if ($producto->codigo_barras === $codigo) {
                return $indice;
            }
        }
        return -1;
    }

    public function actualizarProductoDeVenta(Request $request)
    {
        $codigo = $request->post("codigo");
        $indice = $request->post("indice");
        $cantidad = $request->post("cantidad");
        
        $productos = $this->obtenerProductos();
        $producto = Producto::where("codigo_barras", "=", $codigo)->first();

        if ($cantidad > $producto->existencia) {
            return response()->json([
                    "message" => "No se pueden agregar más productos de este tipo, se quedarían sin existencia",
                    "status" => "error"
                ]);
        }

        $producto_editar = $productos[$indice];
        $producto_editar->cantidad = $cantidad;
        $producto_editar->precio_total = self::redondearAl100($producto_editar->cantidad * $producto_editar->precio_venta);
        $productos[$indice] = $producto_editar;

        $this->guardarProductos($productos);

        return response()->json([
            "message" => "Cantidad Actualizada correctamente",
            "status" => "success"
        ]);
    }


    public function index()
    {
        return view("vender.vender",[
            "clientes" => Cliente::all(),
        ]);
    }

    public function obtenerProductosCarritoJson()
    {
        $total = 0;
        foreach ($this->obtenerProductos() as $producto) {
            $total += self::redondearAl100($producto->cantidad * $producto->precio_venta);
        }

        return response()->json([
            'total' => $total,
            'productosCarrito' => $this->obtenerProductos() ,
        ]);
    }
}
