<?php
namespace App\Http\Controllers;

use App\Producto;
use App\Proveedor;
use Illuminate\Http\Request;

use DB;

use PDF;

use GuzzleHttp\Client;

class ProductosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Sin la columna imagen (base64): las fotos se piden aparte con productos.imagen
        $query = Producto::select('productos.id', 'codigo_barras', 'descripcion', 'categoria', 'precio_compra', 'precio_venta', 'existencia', 'unidad_medida', 'id_proveedor', 'productos.created_at', 'productos.updated_at')
            ->selectRaw("(imagen IS NOT NULL AND imagen <> '') as tiene_imagen")
            ->with('proveedor:id,nombre');

        $idProveedor = (int) $request->input('proveedor');
        if ($idProveedor) {
            $query->where('id_proveedor', $idProveedor);
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', '%' . $search . '%')
                    ->orWhere('codigo_barras', 'like', '%' . $search . '%')
                    ->orWhere('categoria', 'like', '%' . $search . '%');
            });
        }

        $categoria = $request->input('categoria');
        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        $stock = $request->input('stock');
        if ($stock === 'bajo') {
            $query->where('existencia', '<', Producto::UMBRAL_ALERTA);
        } elseif ($stock === 'agotado') {
            $query->where('existencia', '<=', 0);
        }

        $productos = $query->orderBy('descripcion')->paginate(20);

        foreach ($productos as $producto) {
            $ganancia = $producto->precio_venta - $producto->precio_compra;
            $producto->ganancia = $ganancia;
            $producto->porcentaje = $producto->precio_compra > 0 ? round(($ganancia / $producto->precio_compra) * 100, 2) : null;
        }

        return view("productos.productos_index", [
            "productos" => $productos,
            "categorias" => self::CATEGORIAS,
            "proveedores" => Proveedor::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Foto de un producto (guardada en base64 en la tabla). Se cachea en el navegador.
     */
    public function imagen(Producto $producto)
    {
        if (!$producto->imagen) {
            abort(404);
        }
        $bytes = base64_decode($producto->imagen, true);
        if ($bytes === false) {
            abort(404);
        }
        $tipo = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'image/jpeg';

        return response($bytes, 200, [
            'Content-Type' => $tipo,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    // Categorías que ofrece el formulario (mismas de siempre)
    const CATEGORIAS = [
        'Alimentos' => 'fa-apple-alt',
        'Bebidas' => 'fa-wine-bottle',
        'Aseo' => 'fa-spray-can',
        'Carnes' => 'fa-drumstick-bite',
        'Farmacia' => 'fa-prescription-bottle-alt',
        'Fritos' => 'fa-cookie-bite',
        'Otros' => 'fa-box',
    ];

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("productos.productos_create", [
            "categorias" => self::CATEGORIAS,
            "proveedores" => Proveedor::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $request->validate([
            'codigo_barras' => 'required',
            'descripcion' => 'required',
            'categoria' => 'required',
            'precio_compra' => 'required|numeric',
            'precio_venta' => 'required|numeric',
            'existencia' => 'required|numeric',
            'unidad_medida' => 'required',
            'imagen' => 'nullable',
            'id_proveedor' => 'nullable|exists:proveedores,id',
        ]);

        $base64Image = "";
        if ($request->hasFile('imagen')) {
            $filePath = $request->file('imagen')->path();
            $fileContent = file_get_contents($filePath);
            $base64Image = base64_encode($fileContent);
        } else {
            $base64Image = "";
        }

        $producto = new Producto($request->except('imagen'));
        $producto->imagen = $base64Image;
        $producto->saveOrFail();

        // A la tienda en línea se envían los mismos campos de siempre (sin el proveedor, que es local)
        $this->registrarProductoNube($request->except(['imagen', 'id_proveedor']), $producto->imagen);
        return redirect()->route("productos.create")
            ->with("mensaje", "Producto registrado: " . $producto->descripcion)
            ->with("tipo", "success");
    }

    public function registrarProductoNube($producto, $base64Image){
        if (checkdnsrr('example.com', 'A')) {
            $client = new Client();

            $url = 'https://provisiones-carlosandres.shop/registrar_producto.php';

            $data = [
                'producto' => json_encode($producto),
                'imagen' => $base64Image
            ];

            $response = $client->post($url, [
                'form_params' => $data
            ]);
            
            $response = $response->getBody();
            $body = json_decode($response, true);
            return $body;
        }
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Producto $producto
     * @return \Illuminate\Http\Response
     */
    public function show(Producto $producto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Producto $producto
     * @return \Illuminate\Http\Response
     */
    public function edit(Producto $producto)
    {
        return view("productos.productos_edit", [
            "producto" => $producto,
            "categorias" => self::CATEGORIAS,
            "proveedores" => Proveedor::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Producto $producto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Producto $producto)
    {
        $request->validate(['id_proveedor' => 'nullable|exists:proveedores,id']);
        $producto->fill($request->input());
        
        $base64Image = "";
        if ($request->hasFile('imagen')) {
            $filePath = $request->file('imagen')->path();
            $fileContent = file_get_contents($filePath);
            $base64Image = base64_encode($fileContent);
        } else {
            $base64Image = $producto->imagen;
        }

        $producto->imagen = $base64Image;
        $producto->saveOrFail();
        $this->actualizarProductoNube($producto);
        return redirect()->route("productos.index")->with("mensaje", "Producto actualizado: " . $producto->descripcion)->with("tipo", "success");
    }

    public function actualizarProductoNube($producto){
        if (checkdnsrr('example.com', 'A')) {
            $client = new Client();

            $url = 'https://provisiones-carlosandres.shop/actualizar_producto_nube.php';

            // Mismos campos de siempre: el proveedor es solo local
            $data = [
                'producto' => json_encode(collect($producto->toArray())->except(['id_proveedor', 'proveedor'])),
            ];

            $response = $client->post($url, [
                'form_params' => $data
            ]);
            
            $response = $response->getBody();
            $body = json_decode($response, true);
            return $body;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Producto $producto
     * @return \Illuminate\Http\Response
     */
    public function destroy(Producto $producto)
    {
        $this->eliminarProductoNube($producto->codigo_barras);
        $producto->delete();
        return redirect()->route("productos.index")->with("mensaje", "Producto eliminado");
    }

    public function eliminarProductoNube($codigo_producto){
        if (checkdnsrr('example.com', 'A')) {
            $client = new Client();
            $url = 'https://provisiones-carlosandres.shop/eliminar_producto.php';
            $data = [
                'codigo_producto' => $codigo_producto
            ];

            // Antes esto estaba fuera del "if": sin internet, eliminar un producto daba error
            $response = $client->post($url, [
                'form_params' => $data
            ]);

            $response = $response->getBody();
            $body = json_decode($response, true);
            return $body;
        }
    }

    public function productosCategoria(Request $request){
        // ?ligero=1 (buscador de Vender): sin la foto en base64, que pesa ~11 MB en total
        if ($request->boolean('ligero')) {
            $productos = DB::connection('mysql')->table('productos')
                ->select('id', 'codigo_barras', 'descripcion', 'categoria', 'precio_compra', 'precio_venta', 'existencia', 'unidad_medida', 'id_proveedor', 'updated_at')
                ->selectRaw("(imagen IS NOT NULL AND imagen <> '') as tiene_imagen")
                ->orderBy('descripcion')
                ->get();
            return response()->json($productos);
        }

        $productos = DB::connection('mysql')->table('productos')
        ->get();
        return response()->json($productos);
    }

    public function modificarInventarioProducto(Request $request){

        $cantidad_disponible = $request->input('cantidad_disponible');
        $precio_compra = $request->input('precio_compra');
        $precio_venta = $request->input('precio_venta');
        $nueva_cantidad = $request->input('nueva_cantidad');
        $codigo_producto = $request->input('codigo_producto');

        $nueva_cantidad_disponible = $cantidad_disponible + $nueva_cantidad;

        
        DB::connection('mysql')->table('productos')
        ->where('codigo_barras', $codigo_producto)
        ->update([
            'existencia' => $nueva_cantidad_disponible,
            'precio_compra' => $precio_compra,
            'precio_venta' => $precio_venta
        ]);

        $this->modificarInventarioProductoNube($codigo_producto, $nueva_cantidad_disponible, $precio_compra, $precio_venta);
        return redirect()->route("productos.index")->with("mensaje", "Producto actualizado");
    }

    public function modificarInventarioProductoNube($codigo_producto, $nueva_cantidad_disponible, $precio_compra, $precio_venta){
        if (checkdnsrr('example.com', 'A')) {
            $client = new Client();

            $url = 'https://provisiones-carlosandres.shop/modificar_inventario.php';

            $data = [
                'codigo_producto' => $codigo_producto,
                'existencia' => $nueva_cantidad_disponible,
                'precio_compra' => $precio_compra,
                'precio_venta' => $precio_venta
            ];

            $response = $client->post($url, [
                'form_params' => $data
            ]);
            
            $response = $response->getBody();
            $body = json_decode($response, true);
            return $body;
        }
    }

    public function modificarCodigoProducto(Request $request){
        $codigo_anterior = $request->input('codigo_anterior');
        $codigo_nuevo = $request->input('codigo_nuevo');

        DB::connection('mysql')->table('productos')
        ->where('codigo_barras', $codigo_anterior)
        ->update([
            'codigo_barras' => $codigo_nuevo
        ]);

        DB::connection('mysql')->table('productos_vendidos')
        ->where('codigo_barras', $codigo_anterior)
        ->update([
            'codigo_barras' => $codigo_nuevo
        ]);

        $this->modificarCodigoProductoNube($codigo_anterior, $codigo_nuevo);
        return redirect()->route("productos.index")->with("mensaje", "Código de barras actualizado");
    }

    public function modificarCodigoProductoNube($codigo_anterior, $codigo_nuevo){
        if (checkdnsrr('example.com', 'A')) {
            $client = new Client();

            $url = 'https://provisiones-carlosandres.shop/actualizar_producto.php';

            $data = [
                'codigo_anterior' => $codigo_anterior,
                'codigo_nuevo' => $codigo_nuevo,
            ];

            $response = $client->post($url, [
                'form_params' => $data
            ]);
            
            $body = $response->getBody();

            return $body;
        }
    }

    public function verificarUnidadProducto(Request $request){
        $codigo = $request->input('codigo');

        $producto = DB::connection('mysql')->table('productos')
        ->where('codigo_barras', $codigo)
        ->first();

        if($producto == null){
            return 0;
        }else{
            return response()->json($producto);
        }

    }


    /**
     * "Stock bajo": el mismo listado de productos con el filtro ya aplicado
     * (antes era una pantalla aparte casi igual).
     */
    public function alert(Request $request){
        $request->merge(['stock' => $request->input('stock', 'bajo')]);
        return $this->index($request);
    }

    /**
     * PDF del inventario. Respeta los filtros del listado (?search, ?categoria, ?stock).
     */
    public function generarPDF(Request $request)
    {
        // Sin la columna imagen (base64): no hace falta y pesa ~11 MB
        $consulta = DB::connection('mysql')->table('productos')
            ->select('codigo_barras', 'descripcion', 'categoria', 'precio_compra', 'precio_venta', 'existencia', 'unidad_medida')
            ->orderBy('descripcion');

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $consulta->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', '%' . $search . '%')
                    ->orWhere('codigo_barras', 'like', '%' . $search . '%')
                    ->orWhere('categoria', 'like', '%' . $search . '%');
            });
        }
        if ($request->input('categoria')) {
            $consulta->where('categoria', $request->input('categoria'));
        }
        $proveedor = (int) $request->input('proveedor') ? Proveedor::find((int) $request->input('proveedor')) : null;
        if ($proveedor) {
            $consulta->where('id_proveedor', $proveedor->id);
        }
        $stock = $request->input('stock');
        if ($stock === 'bajo') {
            $consulta->where('existencia', '<', Producto::UMBRAL_ALERTA);
        } elseif ($stock === 'agotado') {
            $consulta->where('existencia', '<=', 0);
        }
        $productos = $consulta->get();

        $total_mercancia = 0;
        foreach ($productos as $key) {
            $total_mercancia += ($key->precio_venta * $key->existencia);
            $key->total = ($key->precio_venta * $key->existencia);
        }

        $titulo = $stock === 'bajo' ? 'Productos con stock bajo' : ($stock === 'agotado' ? 'Productos agotados' : 'Inventario de productos');
        if ($request->input('categoria')) {
            $titulo .= ' (' . $request->input('categoria') . ')';
        }
        if ($proveedor) {
            $titulo .= ' de ' . $proveedor->nombre;
        }

        $pdf = PDF::loadView('pdf_productos', ["productos" => $productos, "total" => $total_mercancia, "titulo" => $titulo]);
        $pdf->getDomPDF()->set_option("enable_font_subsetting", true);

        return $pdf->download(mb_strtolower($titulo) . '.pdf');
    }

}
