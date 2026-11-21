<?php

namespace App\Http\Controllers;

use App\Producto;
use App\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Compras a proveedores. La tabla `compras` guarda la fecha como texto "d-m-Y" y el total como texto.
 * Una compra puede llevar productos (`compras_detalle`): al guardarla se suma la cantidad a la
 * existencia y se actualizan los precios de compra y venta de cada producto.
 */
class ComprasController extends Controller
{
    public function index()
    {
        $tabla = DB::connection('mysql')->table('compras');

        return view("compras.compras_index", [
            "compras" => (clone $tabla)
                ->select("compras.*")
                ->selectRaw("(SELECT COUNT(*) FROM compras_detalle d WHERE d.id_compra = compras.id) as numero_productos")
                ->orderBy("id", "DESC")
                ->paginate(50),
            "totalCompradoHoy" => (clone $tabla)->where("fecha", date("d-m-Y"))->sum("total"),
            "totalCompradoMes" => (clone $tabla)->where("fecha", "like", "%-" . date("m-Y"))->sum("total"),
            "totalComprado" => (clone $tabla)->sum("total"),
        ]);
    }

    public function create()
    {
        return view("compras.compras_create", [
            "proveedores" => Proveedor::orderBy("nombre")->get(["id", "nombre"]),
        ]);
    }

    public function show($id)
    {
        $compra = DB::table("compras")->where("id", $id)->first();
        abort_unless($compra, 404);

        return view("compras.compras_show", [
            "compra" => $compra,
            "detalle" => DB::table("compras_detalle")->where("id_compra", $compra->id)->orderBy("id")->get(),
        ]);
    }

    /**
     * Guarda la compra. Campos: id_proveedor, total (solo si no hay productos) y
     * productos[i][id|cantidad|precio_compra|precio_venta].
     * La unidad de medida siempre es la del producto (no se puede cambiar desde la compra).
     */
    public function guardarCompra(Request $request)
    {
        $datos = $request->validate([
            "id_proveedor" => "required|exists:proveedores,id",
            "total" => "nullable|numeric|min:0",
            "productos" => "nullable|array",
            "productos.*.id" => "required|exists:productos,id",
            "productos.*.cantidad" => "required|numeric|gt:0",
            "productos.*.precio_compra" => "required|numeric|min:0",
            "productos.*.precio_venta" => "required|numeric|min:0",
        ], [
            "id_proveedor.required" => "Elige el proveedor de la compra.",
            "productos.*.cantidad.gt" => "La cantidad de cada producto debe ser mayor que cero.",
        ], [
            "id_proveedor" => "proveedor", "total" => "valor",
            "productos.*.cantidad" => "cantidad", "productos.*.precio_compra" => "precio de compra", "productos.*.precio_venta" => "precio de venta",
        ]);

        $lineas = array_values($datos["productos"] ?? []);
        if (!$lineas && !isset($datos["total"])) {
            throw ValidationException::withMessages(["total" => "Escribe el valor pagado o agrega los productos que compraste."]);
        }

        $productos = Producto::whereIn("id", array_column($lineas, "id"))->get()->keyBy("id");
        foreach ($lineas as $linea) {
            $p = $productos[$linea["id"]];
            if ($p->unidad_medida === "Unidades" && floor($linea["cantidad"]) != $linea["cantidad"]) {
                throw ValidationException::withMessages(["productos" => "\"{$p->descripcion}\" se vende por unidades: la cantidad debe ser un número entero."]);
            }
        }
        if (count($lineas) !== count(array_unique(array_column($lineas, "id")))) {
            throw ValidationException::withMessages(["productos" => "Un producto está repetido en la compra: deja una sola línea por producto."]);
        }

        $proveedor = Proveedor::find($datos["id_proveedor"]);
        $total = $lineas
            ? array_sum(array_map(function ($l) { return $l["cantidad"] * $l["precio_compra"]; }, $lineas))
            : (float) $datos["total"];

        $actualizados = [];
        DB::transaction(function () use ($proveedor, $total, $lineas, $productos, &$actualizados) {
            $idCompra = DB::table("compras")->insertGetId([
                "proveedor" => $proveedor->nombre,
                "id_proveedor" => $proveedor->id,
                "total" => round($total, 2),
                "fecha" => date("d-m-Y"),
            ]);

            foreach ($lineas as $linea) {
                $p = $productos[$linea["id"]];
                DB::table("compras_detalle")->insert([
                    "id_compra" => $idCompra,
                    "id_producto" => $p->id,
                    "codigo_barras" => $p->codigo_barras,
                    "descripcion" => $p->descripcion,
                    "unidad_medida" => $p->unidad_medida,
                    "cantidad" => $linea["cantidad"],
                    "precio_compra" => $linea["precio_compra"],
                    "precio_venta" => $linea["precio_venta"],
                    "precio_compra_anterior" => $p->precio_compra,
                    "precio_venta_anterior" => $p->precio_venta,
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);
                // Suma a la existencia (en SQL, por si se vende al mismo tiempo) y precios nuevos
                DB::table("productos")->where("id", $p->id)->update([
                    "existencia" => DB::raw("existencia + " . (float) $linea["cantidad"]),
                    "precio_compra" => $linea["precio_compra"],
                    "precio_venta" => $linea["precio_venta"],
                    "updated_at" => now(),
                ]);
                $actualizados[] = $p->id;
            }
        });

        $this->sincronizarInventario($actualizados);

        $mensaje = "Compra registrada: " . $proveedor->nombre . ($lineas ? ". Se actualizó el inventario de " . count($lineas) . (count($lineas) === 1 ? " producto." : " productos.") : "");
        return redirect()->route("compras.index")->with("mensaje", $mensaje)->with("tipo", "success");
    }

    /**
     * Elimina la compra. Si tenía productos, resta del inventario lo que la compra había sumado
     * (los precios se dejan como están).
     */
    public function eliminarCompra(Request $request)
    {
        $id = $request->input('id_compra');
        $revertidos = [];

        DB::transaction(function () use ($id, &$revertidos) {
            foreach (DB::table("compras_detalle")->where("id_compra", $id)->get() as $linea) {
                if ($linea->id_producto && DB::table("productos")->where("id", $linea->id_producto)->exists()) {
                    DB::table("productos")->where("id", $linea->id_producto)->update([
                        "existencia" => DB::raw("existencia - " . (float) $linea->cantidad),
                        "updated_at" => now(),
                    ]);
                    $revertidos[] = $linea->id_producto;
                }
            }
            DB::table("compras_detalle")->where("id_compra", $id)->delete();
            DB::table("compras")->where("id", $id)->delete();
        });

        $this->sincronizarInventario($revertidos);

        return redirect()->route("compras.index")
            ->with("mensaje", $revertidos ? "Compra eliminada. Se restó del inventario lo que había sumado." : "Compra eliminada")
            ->with("tipo", "success");
    }

    /**
     * Igual que "Inventario y precio" en Productos: avisa a la tienda en línea la existencia y los precios.
     */
    private function sincronizarInventario(array $ids)
    {
        if (!$ids) {
            return;
        }
        $productosController = new ProductosController();
        foreach (Producto::whereIn("id", array_unique($ids))->get() as $p) {
            try {
                $productosController->modificarInventarioProductoNube($p->codigo_barras, $p->existencia, $p->precio_compra, $p->precio_venta);
            } catch (\Throwable $e) {
                report($e); // sin internet o la tienda en línea no responde: la compra ya quedó guardada aquí
            }
        }
    }
}
