@extends("maestra")
@section("titulo", "Registrar compra")
@section("contenido")
<div class="pagina">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('compras.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Compras</a>
            <h1 class="titulo-pagina">Registrar compra</h1>
            <p class="subtitulo-pagina">Agrega los productos que llegaron: se suma la cantidad al inventario y se guardan los precios nuevos.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa estos datos:</strong>
            <ul class="mb-0">@foreach(collect($errors->all())->unique() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('compras.guardarCompra') }}" id="formCompra" autocomplete="off"
          data-url-catalogo="{{ route('productosCategoria', ['ligero' => 1]) }}"
          data-url-proveedor="{{ route('proveedores.store') }}"
          data-lineas-previas="{{ json_encode(old('productos', [])) }}">
        @csrf
        <div class="compra">
            {{-- ============ Productos comprados ============ --}}
            <section class="panel compra-productos">
                <div class="compra-buscador">
                    <label class="vender-codigo compra-buscar">
                        <span class="oculto">Buscar producto o escanear código</span>
                        <i class="fas fa-barcode" aria-hidden="true"></i>
                        <input id="buscarProducto" class="form-control input-grande" placeholder="Escanea el código o escribe el nombre del producto" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="sugerencias">
                    </label>
                    <div class="sugerencias" id="sugerencias" role="listbox" hidden></div>
                </div>

                <div class="tabla-contenedor">
                    <table class="tabla-pos tabla-compra">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="num">Cantidad</th>
                                <th class="num">Precio de compra</th>
                                <th class="num">Precio de venta</th>
                                <th class="num">Subtotal</th>
                                <th class="carrito-quitar"><span class="oculto">Quitar</span></th>
                            </tr>
                        </thead>
                        <tbody id="lineasCompra"></tbody>
                    </table>
                </div>
                <div class="compra-vacia" id="compraVacia">
                    <i class="fas fa-dolly" aria-hidden="true"></i>
                    <p><strong>Sin productos todavía.</strong><br>Búscalos arriba para sumar su existencia y actualizar precios.
                    Si solo quieres anotar el pago, escribe el valor a la derecha.</p>
                </div>
                <p class="ayuda-campo mb-0 compra-nota">La unidad de medida es la del producto y no se cambia desde la compra. Los precios vienen con los valores actuales: cámbialos si llegaron distintos.</p>
            </section>

            {{-- ============ Resumen ============ --}}
            <aside class="compra-resumen">
                <section class="panel">
                    <label for="id_proveedor" class="etiqueta-campo">Proveedor</label>
                    <select id="id_proveedor" name="id_proveedor" class="selectpicker" data-live-search="true" data-width="100%" data-size="8"
                            data-live-search-placeholder="Buscar proveedor" data-none-results-text="Ningún proveedor coincide con {0}" title="Elige el proveedor">
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @if((string) old('id_proveedor') === (string) $proveedor->id) selected @endif>{{ $proveedor->nombre }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-link px-0 mt-1" id="botonNuevoProveedor"><i class="fas fa-plus" aria-hidden="true"></i> Nuevo proveedor</button>

                    <div class="compra-total">
                        <span class="etiqueta-campo" id="etiquetaTotal">Valor pagado</span>
                        <input id="totalManual" class="form-control input-grande cifra" inputmode="numeric" placeholder="$ 0" value="{{ old('total') ? number_format(old('total'), 0, ',', '.') : '' }}">
                        <input type="hidden" name="total" id="totalNumero" value="{{ old('total') }}">
                        <div class="compra-total-auto cifra" id="totalAuto" hidden>$ 0</div>
                        <p class="ayuda-campo mb-0" id="ayudaTotal">Sin productos, escribe cuánto se pagó.</p>
                    </div>

                    <p class="compra-efecto" id="compraEfecto" hidden></p>
                    <button type="submit" class="btn btn-mazorca btn-block compra-guardar" id="botonGuardar">Guardar compra</button>
                    <p class="ayuda-campo mb-0 mt-2">Fecha: {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</p>
                </section>
            </aside>
        </div>
        <div id="camposProductos"></div>
    </form>
</div>

{{-- Alta rápida de proveedor --}}
<div class="modal fade" id="modalProveedor" tabindex="-1" role="dialog" aria-labelledby="tituloNuevoProveedor" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="tituloNuevoProveedor">Nuevo proveedor</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nuevoProveedorNombre">Nombre</label>
                    <input id="nuevoProveedorNombre" class="form-control input-grande" maxlength="150" autocomplete="off" placeholder="Ej.: Distribuidora Postobón">
                </div>
                <div class="form-group mb-0">
                    <label for="nuevoProveedorTelefono">Teléfono <small class="text-muted">(opcional)</small></label>
                    <input id="nuevoProveedorTelefono" class="form-control input-grande cifra" maxlength="40" autocomplete="off" inputmode="tel">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarNuevoProveedor">Agregar y elegir</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ url('/js/compras.js') }}?v={{ filemtime(public_path('js/compras.js')) }}"></script>
@endsection
