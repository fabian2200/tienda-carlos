<?php

namespace App\Http\Controllers;

use App\Cliente;
use Illuminate\Http\Request;

class ClientesController extends Controller
{
    /**
     * Listado con el número de ventas de cada cliente (para advertir antes de eliminar).
     */
    public function index()
    {
        $clientes = Cliente::select("clientes.*")
            ->selectRaw("(SELECT COUNT(*) FROM ventas WHERE ventas.id_cliente = clientes.id) as numero_ventas")
            ->orderBy("nombre")
            ->get();

        return view("clientes.clientes_index", ["clientes" => $clientes]);
    }

    public function create()
    {
        return view("clientes.clientes_create");
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $cliente = new Cliente($datos);
        $cliente->saveOrFail();
        return redirect()->route("clientes.index")
            ->with("mensaje", "Cliente agregado: " . $cliente->nombre)
            ->with("tipo", "success");
    }

    public function show(Cliente $cliente)
    {
        //
    }

    public function edit(Cliente $cliente)
    {
        return view("clientes.clientes_edit", ["cliente" => $cliente]);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->fill($this->validar($request));
        $cliente->saveOrFail();
        return redirect()->route("clientes.index")
            ->with("mensaje", "Cliente actualizado: " . $cliente->nombre)
            ->with("tipo", "success");
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return redirect()->route("clientes.index")->with("mensaje", "Cliente eliminado")->with("tipo", "success");
    }

    private function validar(Request $request)
    {
        return $request->validate([
            "nombre" => "required|string|max:255",
            "telefono" => "required|string|max:255",
        ], [], ["telefono" => "teléfono"]);
    }
}
