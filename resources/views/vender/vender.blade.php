@extends("maestra")
@section("titulo", "Vender")
@section("menu_compacto", "1")
@section("clase_body", "pagina-vender")
@section("contenido")
<div class="vender" id="vender"
     data-token="{{ csrf_token() }}"
     data-url-carrito="{{ route('vender.obtenerProductosCarritoJson') }}"
     data-url-agregar="{{ route('agregarProductoVenta') }}"
     data-url-actualizar="{{ route('actualizarProductoDeVenta') }}"
     data-url-terminar="{{ route('terminarOCancelarVenta') }}"
     data-url-unidad="{{ route('verificarUnidadProducto') }}"
     data-url-buscar="{{ route('productosCategoria', ['ligero' => 1]) }}"
     data-url-peso="{{ url('/leer-peso') }}"
     data-url-imagenes="{{ url('/productos') }}">

    {{-- ============ Izquierda: escanear y carrito ============ --}}
    <div class="vender-izq">
        <form id="formCodigo" class="vender-escaner" autocomplete="off">
            <label class="vender-codigo">
                <span class="oculto">Código de barras</span>
                <i class="fas fa-barcode" aria-hidden="true"></i>
                <input id="codigo" class="form-control input-grande" placeholder="Escanea el código de barras" autofocus>
            </label>
            <button type="button" class="btn btn-secondary vender-boton" id="botonBuscar">
                <i class="fas fa-search" aria-hidden="true"></i> Buscar producto <span class="tecla">F2</span>
            </button>
        </form>

        <div class="carrito">
            <div class="carrito-lista">
                <table class="tabla-carrito">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="num">Precio</th>
                            <th class="centro">Cantidad</th>
                            <th class="num">Subtotal</th>
                            <th class="carrito-quitar"><span class="oculto">Quitar</span></th>
                        </tr>
                    </thead>
                    <tbody id="filas">
                        <tr class="carrito-vacio"><td colspan="5">Cargando la venta…</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="carrito-pie">
                <span><span class="tecla">F2</span> Buscar</span>
                <span><span class="tecla">F4</span> Recibido</span>
                <span><span class="tecla">F8</span> Balanza</span>
                <span><span class="tecla">F9</span> Cobrar</span>
                <span><span class="tecla">Supr</span> Quitar el último</span>
                <span class="carrito-cuenta" id="cuenta"></span>
            </div>
        </div>
    </div>

    {{-- ============ Derecha: la tirilla es el panel de cobro ============ --}}
    <form class="tirilla-cobro" id="formCobro" aria-label="Cobro" autocomplete="off">
        <div class="tirilla-cabecera">
            <strong>{{ mb_strtoupper($negocio->nombre) }}</strong>
            <span>NIT {{ $negocio->nit }}</span>
            <span>{{ $negocio->direccion }}</span>
        </div>

        <div class="tirilla-total">
            <span class="etiqueta">Total<br><span id="articulos">0 productos</span></span>
            <b class="cifra" id="totalVenta">$ 0</b>
        </div>

        <div class="cobro-campo">
            <label for="cliente">Cliente</label>
            <select id="cliente" name="id_cliente" class="selectpicker" data-live-search="true" data-width="100%" data-size="7" data-live-search-placeholder="Buscar cliente" data-none-results-text="Ningún cliente coincide con {0}">
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @if($loop->first) selected @endif>{{ $cliente->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="cobro-fila">
            <div class="cobro-campo">
                <label for="recibido">Recibido</label>
                <input id="recibido" class="form-control cifra cobro-recibido" inputmode="numeric" placeholder="Exacto">
            </div>
            <div class="cobro-campo">
                <span class="cobro-etiqueta" id="etiquetaCambio">Cambio</span>
                <div class="cobro-cambio cifra" id="cambio" aria-live="polite">$ 0</div>
            </div>
        </div>

        <div class="billetes" aria-label="Valores rápidos de lo recibido">
            <button type="button" class="js-billete" data-valor="0">Exacto</button>
            <button type="button" class="js-billete cifra" data-valor="20000">20.000</button>
            <button type="button" class="js-billete cifra" data-valor="50000">50.000</button>
            <button type="button" class="js-billete cifra" data-valor="100000">100.000</button>
            <button type="button" class="js-billete cifra" data-valor="200000">200.000</button>
        </div>

        <div class="cobro-opciones">
            <label class="chip"><input type="checkbox" id="fiar"><span><i class="fas fa-book" aria-hidden="true"></i> Fiar lo que falta</span></label>
            <label class="chip"><input type="checkbox" id="imprimir" checked><span><i class="fas fa-print" aria-hidden="true"></i> Imprimir ticket</span></label>
        </div>

        <div class="cobro-acciones">
            <button type="submit" class="btn btn-mazorca cobro-cobrar" id="botonCobrar">Cobrar <span class="tecla">F9</span></button>
            <button type="button" class="cobro-cancelar" id="botonCancelar">Cancelar venta</button>
        </div>
    </form>
</div>

{{-- ============ Ventana de producto: buscar a mano o pesar ============ --}}
<div class="modal fade" id="modalProducto" tabindex="-1" role="dialog" aria-labelledby="tituloProducto" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="tituloProducto">Agregar producto</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body producto-cuerpo">
                <div class="buscador">
                    <label class="vender-codigo">
                        <span class="oculto">Buscar por nombre o código</span>
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input id="buscarTexto" class="form-control input-grande" placeholder="Escribe el nombre o el código">
                    </label>
                    <div class="resultados" id="resultados"></div>
                </div>

                <div class="producto-detalle">
                    <p class="detalle-vacio" id="detalleVacio">Elige un producto de la lista.</p>
                    <form id="formDetalle" class="detalle-producto" hidden autocomplete="off">
                        <h3 id="detalleNombre"></h3>
                        <p class="detalle-precio cifra" id="detallePrecio"></p>
                        <div class="cobro-fila">
                            <div class="cobro-campo">
                                <label for="detalleCantidad" id="etiquetaCantidad">Cantidad</label>
                                <input id="detalleCantidad" class="form-control input-grande cifra" inputmode="decimal">
                            </div>
                            <div class="cobro-campo">
                                <label for="detalleValor">Valor a vender</label>
                                <input id="detalleValor" class="form-control input-grande cifra" inputmode="numeric" placeholder="$ 0">
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary detalle-balanza" id="botonBalanza" hidden>
                            <i class="fas fa-balance-scale" aria-hidden="true"></i> Leer balanza <span class="tecla">F8</span>
                        </button>
                        <button type="submit" class="btn btn-primary detalle-agregar">Agregar a la venta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ url('/js/vender.js') }}?v={{ filemtime(public_path('js/vender.js')) }}"></script>
@endsection
