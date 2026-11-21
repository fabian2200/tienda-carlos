@extends("maestra")
@section("titulo", "Venta #" . $venta->id)
@section("contenido")
@php $creada = \Carbon\Carbon::parse($venta->created_at); @endphp
<div class="pagina pagina-angosta">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('ventas.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Ventas</a>
            <h1 class="titulo-pagina">Venta #{{ $venta->id }}</h1>
            <p class="subtitulo-pagina">{{ ucfirst($creada->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY, h:mm a')) }}</p>
        </div>
        <div class="encabezado-acciones">
            <button type="button" class="btn btn-secondary" id="botonReimprimir"><i class="fas fa-print" aria-hidden="true"></i> Reimprimir ticket</button>
            <a class="btn btn-primary" href="{{ route('ventas.pdf', $venta) }}"><i class="fas fa-file-pdf" aria-hidden="true"></i> Descargar PDF</a>
        </div>
    </div>

    @include("notificacion")

    <article class="panel factura">
        <header class="factura-cabecera">
            <div class="factura-negocio">
                @if($negocio->urlLogo())
                    <img class="factura-logo" src="{{ $negocio->urlLogo() }}" alt="">
                @else
                    <span class="factura-logo" aria-hidden="true">{{ $negocio->iniciales() }}</span>
                @endif
                <div>
                    <strong class="factura-nombre">{{ $negocio->nombre }}</strong>
                    <span>NIT {{ $negocio->nit }}@if($negocio->regimen), {{ $negocio->regimen }}@endif</span>
                    <span>{{ $negocio->direccion }}@if($negocio->barrioTexto()), {{ $negocio->barrioTexto() }}@endif</span>
                    @if($negocio->telefono)<span>Tel. {{ $negocio->telefono }}</span>@endif
                </div>
            </div>
            <dl class="factura-datos">
                <div><dt>Venta</dt><dd class="cifra">#{{ $venta->id }}</dd></div>
                <div><dt>Fecha</dt><dd class="cifra">{{ $creada->format('d/m/Y h:i a') }}</dd></div>
                <div><dt>Cliente</dt><dd>{{ optional($venta->cliente)->nombre ?? 'Cliente eliminado' }}</dd></div>
                <div><dt>Vendido por</dt><dd>{{ optional($venta->usuario)->name ?? '-' }}</dd></div>
            </dl>
        </header>

        <div class="tabla-contenedor">
        <table class="tabla-pos factura-tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="num">Cantidad</th>
                    <th class="num">Precio</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->productos as $producto)
                    <tr>
                        <td>
                            <strong class="d-block">{{ $producto->descripcion }}</strong>
                            <small class="text-muted cifra">{{ $producto->codigo_barras }}</small>
                        </td>
                        <td class="num">{{ cantidad($producto->cantidad, 3) }} {{ strtolower($producto->unidad) }}</td>
                        <td class="num">{{ dinero($producto->precio) }}</td>
                        <td class="num"><strong>{{ dinero($producto->subtotal) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="factura-pie">
            <dl class="factura-pago">
                <div><dt>Pagó</dt><dd class="cifra">{{ dinero($venta->total_dinero) }}</dd></div>
                @if($venta->total_fiado > 0)
                    <div class="fiado"><dt>Quedó fiado</dt><dd class="cifra">{{ dinero($venta->total_fiado) }}</dd></div>
                @else
                    <div><dt>Cambio</dt><dd class="cifra">{{ dinero(max(0, $venta->total_vueltos)) }}</dd></div>
                @endif
            </dl>
            <dl class="factura-totales">
                @if($domicilio > 0)
                    <div><dt>Subtotal</dt><dd class="cifra">{{ dinero($subtotal) }}</dd></div>
                    <div><dt>Domicilio</dt><dd class="cifra">{{ dinero($domicilio) }}</dd></div>
                @endif
                <div class="factura-total"><dt>Total</dt><dd class="cifra">{{ dinero($total) }}</dd></div>
            </dl>
        </div>
    </article>
</div>

<script>
document.getElementById("botonReimprimir").addEventListener("click", function () {
    const b = this;
    b.disabled = true;
    const aviso = (t, i) => Swal.fire({ toast: true, position: "top-end", icon: i, title: t, showConfirmButton: false, timer: 3500, timerProgressBar: true, customClass: { popup: "aviso-pos" } });
    $.get(@json(url('/imprimir-ticket')), { id_venta: {{ $venta->id }} })
        .then(() => aviso("Ticket enviado a la impresora.", "success"),
              () => aviso("No se pudo imprimir. Revisa que la impresora esté encendida y conectada.", "error"))
        .always(() => (b.disabled = false));
});
</script>
@endsection
