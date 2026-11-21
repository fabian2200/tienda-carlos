@extends("maestra")
@section("titulo", "Productos")
@section("contenido")
@php
    $filtrando = request('search') || request('categoria') || request('stock') || request('proveedor');
    $abrev = function ($unidad) { return $unidad === 'Kilos' ? 'kg' : ($unidad === 'Libras' ? 'lb' : 'und'); };
@endphp
<div class="pagina">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">{{ request('stock') === 'bajo' ? 'Stock bajo' : (request('stock') === 'agotado' ? 'Productos agotados' : 'Productos') }}</h1>
            <p class="subtitulo-pagina">
                {{ number_format($productos->total(), 0, ',', '.') }} {{ $filtrando ? 'encontrados' : 'productos en el inventario' }}
            </p>
        </div>
        <div class="encabezado-acciones">
            <a href="{{ route('generarPDF', array_filter(request()->only(['search', 'categoria', 'stock', 'proveedor']))) }}" class="btn btn-secondary">
                <i class="fas fa-file-pdf" aria-hidden="true"></i> {{ $filtrando ? 'Exportar esta lista en PDF' : 'Exportar inventario en PDF' }}
            </a>
            <a href="{{ route('productos.create') }}" class="btn btn-mazorca">
                <i class="fas fa-plus" aria-hidden="true"></i> Registrar producto
            </a>
        </div>
    </div>

    @include("notificacion")

    <form class="filtros" method="GET" action="{{ route('productos.index') }}" id="formFiltros" role="search">
        <label class="filtro-buscar">
            <span class="oculto">Buscar por nombre o código de barras</span>
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" name="search" class="form-control" placeholder="Buscar por nombre o código de barras" value="{{ request('search') }}" autocomplete="off" id="filtroBuscar">
        </label>
        <label class="filtro-select">
            <span class="oculto">Categoría</span>
            <select name="categoria" class="custom-select js-autoenviar">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $nombre => $icono)
                    <option value="{{ $nombre }}" @if(request('categoria') === $nombre) selected @endif>{{ $nombre }}</option>
                @endforeach
            </select>
        </label>
        <label class="filtro-select">
            <span class="oculto">Existencia</span>
            <select name="stock" class="custom-select js-autoenviar">
                <option value="">Toda la existencia</option>
                <option value="bajo" @if(request('stock') === 'bajo') selected @endif>Stock bajo (menos de {{ \App\Producto::UMBRAL_ALERTA }})</option>
                <option value="agotado" @if(request('stock') === 'agotado') selected @endif>Agotados</option>
            </select>
        </label>
        @if($proveedores->isNotEmpty())
            <label class="filtro-select">
                <span class="oculto">Proveedor</span>
                <select name="proveedor" class="custom-select js-autoenviar">
                    <option value="">Todos los proveedores</option>
                    @foreach($proveedores as $p)
                        <option value="{{ $p->id }}" @if((int) request('proveedor') === $p->id) selected @endif>{{ $p->nombre }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        @if($filtrando)
            <a class="filtro-limpiar" href="{{ route('productos.index') }}">Quitar filtros</a>
        @endif
        <button type="submit" class="oculto">Buscar</button>
    </form>

    <div class="panel p-0 tabla-contenedor">
        <table class="tabla-pos tabla-productos">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="num">Compra</th>
                    <th class="num">Venta</th>
                    <th class="num">Ganancia</th>
                    <th class="num">Existencia</th>
                    <th class="acciones-titulo"><span class="oculto">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($productos as $producto)
                @php
                    $existencia = (float) $producto->existencia;
                    $estado = $existencia <= 0 ? 'agotado' : ($existencia < \App\Producto::UMBRAL_ALERTA ? 'bajo' : 'ok');
                    $datos = [
                        'id' => $producto->id,
                        'descripcion' => $producto->descripcion,
                        'codigo' => $producto->codigo_barras,
                        'compra' => (float) $producto->precio_compra,
                        'venta' => (float) $producto->precio_venta,
                        'existencia' => $existencia,
                        'unidad' => $abrev($producto->unidad_medida),
                    ];
                @endphp
                <tr data-producto="{{ json_encode($datos) }}">
                    <td>
                        <div class="producto-celda">
                            @if($producto->tiene_imagen)
                                <img class="producto-foto" loading="lazy" src="{{ route('productos.imagen', $producto) }}?v={{ optional($producto->updated_at)->timestamp }}" alt="">
                            @else
                                <span class="producto-foto vacia"><i class="fas {{ $categorias[$producto->categoria] ?? 'fa-box' }}" aria-hidden="true"></i></span>
                            @endif
                            <span class="producto-texto">
                                <strong>{{ $producto->descripcion }}</strong>
                                <small><span class="cifra">{{ $producto->codigo_barras }}</span> <span class="etiqueta-categoria">{{ $producto->categoria }}</span>@if($producto->proveedor) <span class="etiqueta-proveedor"><i class="fas fa-truck" aria-hidden="true"></i> {{ $producto->proveedor->nombre }}</span>@endif</small>
                            </span>
                        </div>
                    </td>
                    <td class="num">{{ dinero($producto->precio_compra) }}</td>
                    <td class="num"><strong>{{ dinero($producto->precio_venta) }}</strong></td>
                    <td class="num">
                        {{ dinero($producto->ganancia) }}
                        @if(!is_null($producto->porcentaje))
                            <small class="d-block {{ $producto->porcentaje < 0 ? 'text-danger' : 'text-muted' }} cifra">{{ number_format($producto->porcentaje, 1, ',', '.') }} %</small>
                        @endif
                    </td>
                    <td class="num">
                        <span class="existencia existencia-{{ $estado }}">
                            {{ cantidad($existencia) }} {{ $abrev($producto->unidad_medida) }}
                        </span>
                        @if($estado === 'agotado')<small class="d-block text-danger">Agotado</small>@elseif($estado === 'bajo')<small class="d-block text-muted">Stock bajo</small>@endif
                    </td>
                    <td class="acciones">
                        <button type="button" class="btn-accion js-inventario" title="Agregar inventario o cambiar precio" aria-label="Agregar inventario o cambiar precio de {{ $producto->descripcion }}"><i class="fas fa-dolly" aria-hidden="true"></i></button>
                        <a class="btn-accion" href="{{ route('productos.edit', $producto) }}" title="Editar producto" aria-label="Editar {{ $producto->descripcion }}"><i class="fas fa-pen" aria-hidden="true"></i></a>
                        <button type="button" class="btn-accion js-codigo" title="Cambiar código de barras" aria-label="Cambiar código de barras de {{ $producto->descripcion }}"><i class="fas fa-barcode" aria-hidden="true"></i></button>
                        <button type="button" class="btn-accion peligro js-eliminar" data-url="{{ route('productos.destroy', $producto) }}" title="Eliminar producto" aria-label="Eliminar {{ $producto->descripcion }}"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="tabla-vacia">
                        @if($filtrando)
                            No hay productos con esos filtros. <a href="{{ route('productos.index') }}">Ver todos</a>
                        @else
                            Aún no hay productos. <a href="{{ route('productos.create') }}">Registrar el primero</a>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">
        {{ $productos->appends(request()->query())->onEachSide(1)->links() }}
    </div>
</div>

{{-- Inventario y precio (también reemplaza al antiguo botón "Cambiar porcentaje") --}}
<div class="modal fade" id="modalInventario" tabindex="-1" role="dialog" aria-labelledby="tituloInventario" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" method="post" action="{{ route('modificarInventarioProducto') }}" autocomplete="off">
            @csrf
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="tituloInventario">Inventario y precio</h2>
                    <p class="modal-subtitulo" id="inventarioNombre"></p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="codigo_producto" id="invCodigo">
                <input type="hidden" name="cantidad_disponible" id="invExistencia">

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="invAgregar">Cantidad que llegó</label>
                        <input class="form-control input-grande cifra" id="invAgregar" name="nueva_cantidad" inputmode="decimal" value="0" required>
                    </div>
                    <div class="form-group col-6">
                        <span class="etiqueta-campo">Quedará en existencia</span>
                        <div class="valor-calculado cifra" id="invResultado">0</div>
                        <small class="text-muted">Hoy hay <span id="invActual" class="cifra"></span></small>
                    </div>
                </div>
                <hr>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="invCompra">Precio de compra</label>
                        <input class="form-control cifra" id="invCompra" name="precio_compra" inputmode="decimal" required>
                    </div>
                    <div class="form-group col-4">
                        <label for="invPorcentaje">Ganancia %</label>
                        <input class="form-control cifra" id="invPorcentaje" inputmode="decimal">
                    </div>
                    <div class="form-group col-4">
                        <label for="invVenta">Precio de venta</label>
                        <input class="form-control cifra" id="invVenta" name="precio_venta" inputmode="decimal" required>
                    </div>
                </div>
                <p class="ayuda-campo mb-0">Si cambias el porcentaje se calcula el precio de venta (redondeado a 100), y al revés.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalCodigo" tabindex="-1" role="dialog" aria-labelledby="tituloCodigo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" method="post" action="{{ route('modificarCodigoProducto') }}" autocomplete="off">
            @csrf
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="tituloCodigo">Cambiar código de barras</h2>
                    <p class="modal-subtitulo" id="codigoNombre"></p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="codigoAnterior">Código actual</label>
                    <input class="form-control cifra" id="codigoAnterior" name="codigo_anterior" readonly>
                </div>
                <div class="form-group">
                    <label for="codigoNuevo">Código nuevo</label>
                    <input class="form-control input-grande cifra" id="codigoNuevo" name="codigo_nuevo" required placeholder="Escanéalo o escríbelo">
                </div>
                <p class="ayuda-campo mb-0">También se cambia en las ventas que ya tienen este producto.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cambiar código</button>
            </div>
        </form>
    </div>
</div>

<form id="formEliminar" method="post" class="d-none">
    @csrf
    @method("delete")
</form>

<script>
(function () {
    const redondear100 = n => Math.round(n / 100) * 100;
    // Precios: el punto separa miles (2.500). Cantidades: punto o coma son decimales (1.5 kg)
    const num = v => Number(String(v).replace(/\./g, "").replace(",", ".")) || 0;
    const numCantidad = v => Number(String(v).replace(",", ".")) || 0;
    const fmt = n => Number(n).toLocaleString("es-CO", { maximumFractionDigits: 3 });
    const datosDe = el => JSON.parse(el.closest("tr").dataset.producto);
    let actual = null;

    // Buscar mientras se escribe (sin tener que presionar Enter)
    const buscar = document.getElementById("filtroBuscar");
    let espera;
    buscar.addEventListener("input", () => { clearTimeout(espera); espera = setTimeout(() => buscar.form.submit(), 500); });
    if (buscar.value) { buscar.focus(); buscar.setSelectionRange(buscar.value.length, buscar.value.length); }
    document.querySelectorAll(".js-autoenviar").forEach(s => s.addEventListener("change", () => s.form.submit()));

    // ---- Inventario y precio ----
    const $c = id => document.getElementById(id);
    function recalcularExistencia() {
        $c("invResultado").textContent = fmt(actual.existencia + numCantidad($c("invAgregar").value)) + " " + actual.unidad;
    }
    function porcentajeDesdeVenta() {
        const compra = num($c("invCompra").value), venta = num($c("invVenta").value);
        $c("invPorcentaje").value = compra > 0 ? String(Math.round(((venta - compra) / compra) * 1000) / 10).replace(".", ",") : "";
    }
    function ventaDesdePorcentaje() {
        const compra = num($c("invCompra").value), p = num($c("invPorcentaje").value);
        if (compra > 0) $c("invVenta").value = fmt(redondear100(compra * (1 + p / 100)));
    }
    document.querySelectorAll(".js-inventario").forEach(b => b.addEventListener("click", () => {
        actual = datosDe(b);
        $c("inventarioNombre").textContent = actual.descripcion;
        $c("invCodigo").value = actual.codigo;
        $c("invExistencia").value = actual.existencia;
        $c("invActual").textContent = fmt(actual.existencia) + " " + actual.unidad;
        $c("invAgregar").value = "0";
        $c("invCompra").value = fmt(actual.compra);
        $c("invVenta").value = fmt(actual.venta);
        porcentajeDesdeVenta();
        recalcularExistencia();
        $("#modalInventario").modal("show");
    }));
    $("#modalInventario").on("shown.bs.modal", () => $c("invAgregar").select());
    $c("invAgregar").addEventListener("input", recalcularExistencia);
    $c("invVenta").addEventListener("input", porcentajeDesdeVenta);
    $c("invCompra").addEventListener("input", ventaDesdePorcentaje);
    $c("invPorcentaje").addEventListener("input", ventaDesdePorcentaje);
    // El servidor espera números con punto decimal
    document.querySelector("#modalInventario form").addEventListener("submit", () => {
        $c("invAgregar").value = numCantidad($c("invAgregar").value);
        ["invCompra", "invVenta"].forEach(id => $c(id).value = num($c(id).value));
    });

    // ---- Cambiar código ----
    document.querySelectorAll(".js-codigo").forEach(b => b.addEventListener("click", () => {
        const p = datosDe(b);
        $c("codigoNombre").textContent = p.descripcion;
        $c("codigoAnterior").value = p.codigo;
        $c("codigoNuevo").value = "";
        $("#modalCodigo").modal("show");
    }));
    $("#modalCodigo").on("shown.bs.modal", () => $c("codigoNuevo").focus());

    // ---- Eliminar (con confirmación) ----
    document.querySelectorAll(".js-eliminar").forEach(b => b.addEventListener("click", () => {
        const p = datosDe(b);
        Swal.fire({
            title: "¿Eliminar este producto?",
            text: p.descripcion + ". Esta acción no se puede deshacer.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "No, conservarlo",
            reverseButtons: true,
        }).then(r => {
            if (!r.isConfirmed) return;
            const f = $c("formEliminar");
            f.action = b.dataset.url;
            f.submit();
        });
    }));
})();
</script>
@endsection
