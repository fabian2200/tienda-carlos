<?php

namespace App\Http\Controllers;

use App\Producto;
use App\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Proveedores: a quién se le compra. Se enlazan con productos (id_proveedor)
 * y con compras (id_proveedor, o el nombre escrito en compras anteriores).
 */
class ProveedoresController extends Controller
{
    // Subconsulta del total comprado: por id, o por el nombre escrito en compras antiguas.
    // `compras` usa otra intercalación (utf8mb4_0900_ai_ci): se fuerza la misma para poder comparar.
    const SQL_COMPRADO = "(SELECT COALESCE(SUM(c.total), 0) FROM compras c WHERE c.id_proveedor = proveedores.id OR (c.id_proveedor IS NULL AND LOWER(c.proveedor) COLLATE utf8mb4_unicode_ci = LOWER(proveedores.nombre)))";

    public function index()
    {
        $proveedores = Proveedor::select("proveedores.*")
            ->selectRaw("(SELECT COUNT(*) FROM productos p WHERE p.id_proveedor = proveedores.id) as numero_productos")
            ->selectRaw(self::SQL_COMPRADO . " as total_comprado")
            ->orderBy("nombre")
            ->get();

        return view("proveedores.proveedores_index", ["proveedores" => $proveedores]);
    }

    public function create()
    {
        return view("proveedores.proveedores_create");
    }

    public function store(Request $request)
    {
        $proveedor = Proveedor::create($this->validar($request));

        // Alta rápida desde el formulario de producto (AJAX)
        if ($request->wantsJson()) {
            return response()->json(["id" => $proveedor->id, "nombre" => $proveedor->nombre], 201);
        }
        return redirect()->route("proveedores.index")
            ->with("mensaje", "Proveedor agregado: " . $proveedor->nombre)
            ->with("tipo", "success");
    }

    public function show(Proveedor $proveedor)
    {
        return redirect()->route("proveedores.edit", $proveedor);
    }

    public function edit(Proveedor $proveedor)
    {
        $compras = DB::table("compras")
            ->where("id_proveedor", $proveedor->id)
            ->orWhere(function ($q) use ($proveedor) {
                $q->whereNull("id_proveedor")->whereRaw("LOWER(proveedor) = ?", [mb_strtolower($proveedor->nombre)]);
            })
            ->orderByDesc("id");

        return view("proveedores.proveedores_edit", [
            "proveedor" => $proveedor,
            "numeroProductos" => Producto::where("id_proveedor", $proveedor->id)->count(),
            "totalComprado" => (clone $compras)->sum("total"),
            "ultimasCompras" => $compras->limit(8)->get(),
        ]);
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $proveedor->fill($this->validar($request, $proveedor))->save();
        return redirect()->route("proveedores.index")
            ->with("mensaje", "Proveedor actualizado: " . $proveedor->nombre)
            ->with("tipo", "success");
    }

    public function destroy(Proveedor $proveedor)
    {
        // Los productos y compras se conservan; solo pierden el enlace
        Producto::where("id_proveedor", $proveedor->id)->update(["id_proveedor" => null]);
        DB::table("compras")->where("id_proveedor", $proveedor->id)->update(["id_proveedor" => null]);
        $proveedor->delete();
        return redirect()->route("proveedores.index")->with("mensaje", "Proveedor eliminado")->with("tipo", "success");
    }

    private function validar(Request $request, Proveedor $proveedor = null)
    {
        return $request->validate([
            "nombre" => "required|string|max:150|unique:proveedores,nombre" . ($proveedor ? "," . $proveedor->id : ""),
            "nit" => "nullable|string|max:30",
            "telefono" => "nullable|string|max:40",
            "contacto" => "nullable|string|max:120",
            "direccion" => "nullable|string|max:150",
            "notas" => "nullable|string|max:255",
        ], [
            "nombre.unique" => "Ya existe un proveedor con ese nombre.",
        ], [
            "nit" => "NIT", "telefono" => "teléfono", "direccion" => "dirección",
        ]);
    }
}
