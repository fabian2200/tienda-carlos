@extends("maestra")
@section("titulo", "Compra a " . $compra->proveedor)
@section("contenido")
@php $abrev = function ($u) { return $u === 'Kilos' ? 'kg' : ($u === 'Libras' ? 'lb' : 'und'); }; @endphp
<div class="pagina pagina-media">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('compras.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Compras</a>
            <h1 class="titulo-pagina">Compra a {{ $compra->proveedor }}</h1>
            <p class="subtitulo-pagina">{{ str_replace('-', '/', $compra->fecha) }}, {{ $detalle->count() }} {{ $detalle->count() == 1 ? 'producto' : 'productos' }}</p>
        </div>
        @if($compra->id_proveedor)
            <div class="encabezado-acciones">
                <a class="btn btn-secondary" href="{{ route('proveedores.edit', $compra->id_proveedor) }}"><i class="fas fa-truck" aria-hidden="true"></i> Ver proveedor</a>
            </div>
        @endif
    </div>

    <div class="panel p-0 tabla-contenedor">
        <table class="tabla-pos">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="num">Cantidad</th>
                    <th class="num">Precio de compra</th>
                    <th class="num">Precio de venta</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            @foreach($detalle as $linea)
                <tr>
                    <td>
                        @if($linea->id_producto)
                            <a href="{{ route('productos.edit', $linea->id_producto) }}"><strong>{{ $linea->descripcion }}</strong></a>
                        @else
                            <strong>{{ $linea->descripcion }}</strong>
                        @endif
                        <small class="d-block text-muted cifra">{{ $linea->codigo_barras }}</small>
                    </td>
                    <td class="num">+{{ cantidad($linea->cantidad, 3) }} {{ $abrev($linea->unidad_medida) }}</td>
                    <td class="num">
                        {{ dinero($linea->precio_compra) }}
                        @if(!is_null($linea->precio_compra_anterior) && (float) $linea->precio_compra_anterior !== (float) $linea->precio_compra)
                            <small class="d-block text-muted">antes {{ dinero($linea->precio_compra_anterior) }}</small>
                        @endif
                    </td>
                    <td class="num">
                        {{ dinero($linea->precio_venta) }}
                        @if(!is_null($linea->precio_venta_anterior) && (float) $linea->precio_venta_anterior !== (float) $linea->precio_venta)
                            <small class="d-block text-muted">antes {{ dinero($linea->precio_venta_anterior) }}</small>
                        @endif
                    </td>
                    <td class="num"><strong>{{ dinero($linea->cantidad * $linea->precio_compra) }}</strong></td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="num"><strong>Total</strong></td>
                    <td class="num"><strong class="cifra total-compra">{{ dinero($compra->total) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
