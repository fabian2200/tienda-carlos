@extends("maestra")
@section("titulo", "Compras")
@section("contenido")
<div class="pagina pagina-media">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">Compras</h1>
            <p class="subtitulo-pagina">Lo que se le paga a proveedores</p>
        </div>
        <div class="encabezado-acciones">
            <a href="{{ route('compras.create') }}" class="btn btn-mazorca"><i class="fas fa-plus" aria-hidden="true"></i> Registrar compra</a>
        </div>
    </div>

    @include("notificacion")

    <section class="inicio-cifras cifras-ventas" aria-label="Resumen de compras">
        <div class="inicio-cifra principal">
            <span class="etiqueta">Comprado hoy</span>
            <span class="valor cifra">{{ dinero($totalCompradoHoy) }}</span>
        </div>
        <div class="inicio-cifra">
            <span class="etiqueta">Este mes</span>
            <span class="valor cifra">{{ dinero($totalCompradoMes) }}</span>
            <span class="detalle">{{ ucfirst(now()->locale('es')->isoFormat('MMMM')) }}</span>
        </div>
        <div class="inicio-cifra">
            <span class="etiqueta">Total registrado</span>
            <span class="valor cifra">{{ dinero($totalComprado) }}</span>
            <span class="detalle">Desde que se usa el sistema</span>
        </div>
    </section>

    <div class="panel p-0 tabla-contenedor">
        <table class="tabla-pos">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th class="num">Productos</th>
                    <th class="num">Valor</th>
                    <th class="acciones-titulo"><span class="oculto">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($compras as $compra)
                <tr>
                    <td class="cifra">{{ str_replace('-', '/', $compra->fecha) }}</td>
                    <td>
                        @if($compra->id_proveedor)
                            <a href="{{ route('proveedores.edit', $compra->id_proveedor) }}"><strong>{{ $compra->proveedor }}</strong></a>
                        @else
                            <strong>{{ $compra->proveedor }}</strong>
                        @endif
                    </td>
                    <td class="num">
                        @if($compra->numero_productos > 0)
                            <a href="{{ route('compras.show', $compra->id) }}">{{ $compra->numero_productos }}</a>
                        @else
                            <span class="text-muted">Sin detalle</span>
                        @endif
                    </td>
                    <td class="num"><strong>{{ dinero($compra->total) }}</strong></td>
                    <td class="acciones">
                        @if($compra->numero_productos > 0)
                            <a class="btn-accion" href="{{ route('compras.show', $compra->id) }}" title="Ver productos de la compra" aria-label="Ver los productos de la compra a {{ $compra->proveedor }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                        @endif
                        <form method="post" action="{{ route('compras.eliminar') }}" class="d-inline js-eliminar-compra">
                            @csrf
                            <input type="hidden" name="id_compra" value="{{ $compra->id }}">
                            <button type="submit" class="btn-accion peligro" title="Eliminar compra" aria-label="Eliminar la compra a {{ $compra->proveedor }}"
                                    data-proveedor="{{ $compra->proveedor }}" data-valor="{{ dinero($compra->total) }}" data-productos="{{ $compra->numero_productos }}"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="tabla-vacia">Aún no hay compras registradas. <a href="{{ route('compras.create') }}">Registrar la primera</a></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="paginacion">{{ $compras->onEachSide(1)->links() }}</div>
</div>

<script>
document.querySelectorAll(".js-eliminar-compra").forEach(f => f.addEventListener("submit", e => {
    e.preventDefault();
    const b = f.querySelector("button");
    const productos = Number(b.dataset.productos) || 0;
    Swal.fire({
        title: "¿Eliminar esta compra?",
        text: b.dataset.proveedor + " por " + b.dataset.valor + "." +
            (productos ? " Se restará del inventario lo que esta compra sumó a sus " + productos + (productos === 1 ? " producto" : " productos") + " (los precios no cambian)." : "") +
            " Esta acción no se puede deshacer.",
        icon: "warning", showCancelButton: true, confirmButtonText: "Sí, eliminar", cancelButtonText: "No, conservarla", reverseButtons: true,
    }).then(r => { if (r.isConfirmed) f.submit(); });
}));
</script>
@endsection
