@extends("maestra")
@section("titulo", "Ventas")
@section("contenido")
@php
    $hoy = now();
    $rapidos = [
        'Hoy' => [$hoy->format('Y-m-d'), $hoy->format('Y-m-d')],
        'Ayer' => [$hoy->copy()->subDay()->format('Y-m-d'), $hoy->copy()->subDay()->format('Y-m-d')],
        'Este mes' => [$hoy->copy()->startOfMonth()->format('Y-m-d'), $hoy->format('Y-m-d')],
        'Mes pasado' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'), $hoy->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d')],
    ];
    $fechaCorta = function ($f) { return \Carbon\Carbon::parse($f)->locale('es')->isoFormat('D MMM YYYY'); };
@endphp
<div class="pagina">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">Ventas</h1>
            <p class="subtitulo-pagina">Ventas de tu caja ({{ Auth::user()->name }})</p>
        </div>
        <div class="encabezado-acciones">
            <a href="{{ route('vender.index') }}" class="btn btn-mazorca"><i class="fas fa-cash-register" aria-hidden="true"></i> Vender</a>
        </div>
    </div>

    @include("notificacion")

    <section class="inicio-cifras cifras-ventas" aria-label="Resumen de tu caja">
        <div class="inicio-cifra principal">
            <span class="etiqueta">Vendido hoy</span>
            <span class="valor cifra">{{ dinero($totalVendidoHoy) }}</span>
            <span class="detalle">{{ $ventasHoy }} {{ $ventasHoy == 1 ? 'venta' : 'ventas' }}</span>
        </div>
        <div class="inicio-cifra">
            <span class="etiqueta">Este mes</span>
            <span class="valor cifra">{{ dinero($totalVendido) }}</span>
            <span class="detalle">{{ ucfirst(now()->locale('es')->isoFormat('MMMM')) }}</span>
        </div>
        <a class="inicio-cifra enlace" href="{{ route('usuarios.deudores') }}">
            <span class="etiqueta">Fiado por cobrar</span>
            <span class="valor cifra">{{ dinero($totalFiado) }}</span>
            <span class="detalle">De todas las cajas</span>
        </a>
    </section>

    <form class="filtros filtros-ventas" method="GET" action="{{ route('ventas.index') }}" autocomplete="off">
        <label class="filtro-fecha">
            <span>Desde</span>
            <input type="date" name="fecha1" class="form-control" value="{{ $fecha1 }}">
        </label>
        <label class="filtro-fecha">
            <span>Hasta</span>
            <input type="date" name="fecha2" class="form-control" value="{{ $fecha2 }}">
        </label>
        <label class="filtro-cliente">
            <span>Cliente</span>
            <select name="cliente" class="selectpicker" data-live-search="true" data-width="100%" data-size="8" data-live-search-placeholder="Buscar cliente" data-none-results-text="Ningún cliente coincide con {0}">
                <option value="">Todos los clientes</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @if($idCliente === $cliente->id) selected @endif>{{ $cliente->nombre }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="btn btn-primary filtro-boton"><i class="fas fa-search" aria-hidden="true"></i> Buscar</button>
        <div class="filtro-rapidos" aria-label="Rangos rápidos">
            @foreach($rapidos as $nombre => $rango)
                <a class="filtro-rapido {{ $fecha1 === $rango[0] && $fecha2 === $rango[1] ? 'activo' : '' }}"
                   href="{{ route('ventas.index', array_filter(['fecha1' => $rango[0], 'fecha2' => $rango[1], 'cliente' => $idCliente])) }}">{{ $nombre }}</a>
            @endforeach
            @if($filtrando)
                <a class="filtro-limpiar" href="{{ route('ventas.index') }}">Quitar filtros</a>
            @endif
        </div>
    </form>

    <div class="resultado-encabezado">
        @if($filtrando)
            <strong>{{ number_format($ventas->total(), 0, ',', '.') }} {{ $ventas->total() == 1 ? 'venta' : 'ventas' }} por <span class="cifra">{{ dinero($totalFiltro) }}</span></strong>
            <span class="text-muted">
                @if($fecha1){{ $fecha1 === $fecha2 ? $fechaCorta($fecha1) : 'del ' . $fechaCorta($fecha1) . ' al ' . $fechaCorta($fecha2) }}@endif
                @if($idCliente) {{ $fecha1 ? 'para' : 'De' }} {{ optional($clientes->firstWhere('id', $idCliente))->nombre }}@endif
            </span>
        @else
            <strong>Todas las ventas de tu caja</strong>
            <span class="text-muted">Las más recientes primero</span>
        @endif
    </div>

    <div class="panel p-0 tabla-contenedor">
        <table class="tabla-pos tabla-ventas">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Venta</th>
                    <th>Cliente</th>
                    <th>Forma de pago</th>
                    <th class="num">Total</th>
                    <th class="acciones-titulo"><span class="oculto">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($ventas as $venta)
                @php $creada = \Carbon\Carbon::parse($venta->created_at); @endphp
                <tr>
                    <td class="cifra">
                        {{ $creada->format('d/m/Y') }}
                        <small class="d-block text-muted">{{ $creada->format('h:i a') }}</small>
                    </td>
                    <td><a class="cifra" href="{{ route('ventas.show', $venta) }}">#{{ $venta->id }}</a></td>
                    <td>{{ $venta->cliente }}</td>
                    <td>
                        @if($venta->total_fiado > 0)
                            <span class="badge badge-warning">Fiado {{ dinero($venta->total_fiado) }}</span>
                        @else
                            <span class="badge badge-success">Contado</span>
                        @endif
                        @if($venta->precio_domi > 0)
                            <span class="badge badge-info">Domicilio</span>
                        @endif
                    </td>
                    <td class="num"><strong>{{ dinero($venta->total_pagar) }}</strong></td>
                    <td class="acciones">
                        <a class="btn-accion" href="{{ route('ventas.show', $venta) }}" title="Ver detalle" aria-label="Ver la venta {{ $venta->id }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                        <button type="button" class="btn-accion js-reimprimir" data-id="{{ $venta->id }}" title="Reimprimir ticket" aria-label="Reimprimir el ticket de la venta {{ $venta->id }}"><i class="fas fa-print" aria-hidden="true"></i></button>
                        <button type="button" class="btn-accion peligro js-eliminar" data-id="{{ $venta->id }}" data-url="{{ route('ventas.destroy', $venta) }}" title="Eliminar venta" aria-label="Eliminar la venta {{ $venta->id }}"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="tabla-vacia">
                        @if($filtrando)
                            No hay ventas con esos filtros. <a href="{{ route('ventas.index') }}">Ver todas</a>
                        @else
                            Aún no hay ventas en tu caja. <a href="{{ route('vender.index') }}">Ir a Vender</a>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">
        {{ $ventas->onEachSide(1)->links() }}
    </div>
</div>

<form id="formEliminar" method="post" class="d-none">
    @csrf
    @method("delete")
</form>

<script>
(function () {
    $(".selectpicker").selectpicker();

    const aviso = (titulo, icono) => Swal.fire({ toast: true, position: "top-end", icon: icono, title: titulo, showConfirmButton: false, timer: 3500, timerProgressBar: true, customClass: { popup: "aviso-pos" } });

    document.querySelectorAll(".js-reimprimir").forEach(b => b.addEventListener("click", () => {
        b.disabled = true;
        $.get(@json(url('/imprimir-ticket')), { id_venta: b.dataset.id })
            .then(() => aviso("Ticket de la venta #" + b.dataset.id + " enviado a la impresora.", "success"),
                  () => aviso("No se pudo imprimir. Revisa que la impresora esté encendida y conectada.", "error"))
            .always(() => (b.disabled = false));
    }));

    document.querySelectorAll(".js-eliminar").forEach(b => b.addEventListener("click", () => {
        Swal.fire({
            title: "¿Eliminar la venta #" + b.dataset.id + "?",
            text: "Se borra la venta con sus productos. Las existencias no se devuelven al inventario y, si fue fiada, esa deuda deja de contarse.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "No, conservarla",
            reverseButtons: true,
        }).then(r => {
            if (!r.isConfirmed) return;
            const f = document.getElementById("formEliminar");
            f.action = b.dataset.url;
            f.submit();
        });
    }));
})();
</script>
@endsection
