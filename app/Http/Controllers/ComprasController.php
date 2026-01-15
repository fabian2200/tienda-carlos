<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComprasController extends Controller
{
    public function index()
    {        
        $compras = DB::connection('mysql')->table('compras')
        ->get();

        $date = date("d-m-Y");

        $compras = DB::connection('mysql')->table('compras')
        ->orderBy("id", "DESC")
        ->get();

        $totalCompradoHoy = DB::connection('mysql')->table('compras')
        ->where("fecha", $date)
        ->sum("total");

        $totalComprado = DB::connection('mysql')->table('compras')
        ->sum("total");
            
        return view("compras.compras_index", [
            "compras" => $compras, 
            "totalCompradoHoy" => $totalCompradoHoy,
            "totalComprado" => $totalComprado
        ]);
    }


    public function guardarCompra(Request $request){
        $total = $request->input('total');
        $proveedor = $request->input('proveedor');
        
        $date = date("d-m-Y");

        DB::connection('mysql')->table('compras')
        ->insert([
            'proveedor' => $proveedor,
            'total' => $total,
            'fecha' => $date
        ]);

        return redirect()->route("compras.index");
    }


    public function eliminarCompra(Request $request){
        $id_compra = $request->input('id_compra');
        
        DB::connection('mysql')->table('compras')
        ->where("id", $id_compra)
        ->delete();

        return redirect()->route("compras.index");
    }
}
