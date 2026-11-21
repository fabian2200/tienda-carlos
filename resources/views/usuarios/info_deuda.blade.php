@extends("maestra")
@section("titulo", "Deuda de " . $cliente->nombre)
@section("contenido")
<div class="pagina pagina-angosta">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('usuarios.deudores') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Fiados</a>
            <h1 class="titulo-pagina">{{ $cliente->nombre }}</h1>
            <p class="subtitulo-pagina">{{ count($facturas_deudas) }} {{ count($facturas_deudas) == 1 ? 'venta fiada' : 'ventas fiadas' }}</p>
        </div>
        <div class="encabezado-acciones">
            <button type="button" class="btn btn-secondary" id="botonImprimirDeuda"><i class="fas fa-print" aria-hidden="true"></i> Imprimir estado de cuenta</button>
        </div>
    </div>

    @include("notificacion")

    <section class="inicio-cifras cifras-ventas" aria-label="Resumen de la deuda">
        <div class="inicio-cifra principal">
            <span class="etiqueta">Debe</span>
            <span class="valor cifra">{{ dinero($cliente->total_deuda) }}</span>
        </div>
        <div class="inicio-cifra">
            <span class="etiqueta">Fiado</span>
            <span class="valor cifra">{{ dinero($cliente->total_fiado) }}</span>
        </div>
        <div class="inicio-cifra">
            <span class="etiqueta">Abonado</span>
            <span class="valor cifra">{{ dinero($cliente->total_abonado) }}</span>
        </div>
    </section>

    @foreach($facturas_deudas as $venta)
        @php $fecha = \Carbon\Carbon::parse($venta->created_at); @endphp
        <article class="panel venta-fiada">
            <header class="venta-fiada-cabecera">
                <div>
                    <a class="cifra venta-fiada-numero" href="{{ route('ventas.show', $venta->id) }}">Venta #{{ $venta->id }}</a>
                    <span class="text-muted">{{ $fecha->format('d/m/Y h:i a') }}</span>
                </div>
                <div class="venta-fiada-montos">
                    <span>Total <strong class="cifra">{{ dinero($venta->total_pagar) }}</strong></span>
                    <span class="badge badge-warning">Fiado {{ dinero($venta->total_fiado) }}</span>
                </div>
            </header>
            <div class="tabla-contenedor">
                <table class="tabla-pos tabla-compacta">
                    <thead>
                        <tr><th>Producto</th><th class="num">Cantidad</th><th class="num">Precio</th><th class="num">Subtotal</th></tr>
                    </thead>
                    <tbody>
                        @foreach($venta->productos as $producto)
                            <tr>
                                <td>{{ $producto->descripcion }}</td>
                                <td class="num">{{ cantidad($producto->cantidad, 3) }} {{ strtolower($producto->unidad) }}</td>
                                <td class="num">{{ dinero($producto->precio) }}</td>
                                <td class="num">{{ dinero(round($producto->cantidad * $producto->precio / 100) * 100) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endforeach
</div>

<script>
document.getElementById("botonImprimirDeuda").addEventListener("click", function () {
    const b = this;
    b.disabled = true;
    const aviso = (t, i) => Swal.fire({ toast: true, position: "top-end", icon: i, title: t, showConfirmButton: false, timer: 3500, timerProgressBar: true, customClass: { popup: "aviso-pos" } });
    $.get(@json(route('usuarios.ImprimirDeuda')), { id_cliente: {{ $cliente->id }} })
        .then(() => aviso("Estado de cuenta enviado a la impresora.", "success"),
              () => aviso("No se pudo imprimir. Revisa que la impresora esté encendida y conectada.", "error"))
        .always(() => (b.disabled = false));
});
</script>
@endsection
