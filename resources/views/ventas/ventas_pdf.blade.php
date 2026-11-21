<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Venta #{{ $venta->id }}</title>
    <style>
        /* dompdf: estilos sencillos y fuente DejaVu (trae tildes y ñ) */
        * { font-family: "DejaVu Sans", sans-serif; }
        body { margin: 0; color: #1D2622; font-size: 11px; }
        .cabecera { width: 100%; border-bottom: 2px solid #1E5B45; padding-bottom: 12px; margin-bottom: 14px; }
        .cabecera td { vertical-align: top; }
        .nombre { font-size: 18px; font-weight: bold; color: #143F30; }
        .gris { color: #5B6862; }
        .derecha { text-align: right; }
        .logo { height: 56px; margin-right: 10px; }
        .numero { font-size: 16px; font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.items th { background: #E3EEE8; color: #143F30; text-align: left; padding: 6px 8px; font-size: 10px; }
        table.items td { padding: 6px 8px; border-bottom: 1px solid #DCE2DE; }
        table.items .num { text-align: right; white-space: nowrap; }
        table.totales { width: 100%; margin-top: 12px; border-collapse: collapse; }
        table.totales .hueco { width: 58%; border: 0 !important; }
        table.totales td { white-space: nowrap; }
        table.totales td { padding: 4px 8px; }
        .total td { font-size: 15px; font-weight: bold; border-top: 2px solid #1E5B45; padding-top: 8px; }
        .total td.hueco { border-top: 0; }
        .pie { margin-top: 28px; text-align: center; color: #5B6862; font-size: 10px; }
    </style>
</head>
<body>
    <table class="cabecera">
        <tr>
            <td>
                @if($negocio->rutaLogo())
                    <img class="logo" src="{{ $negocio->rutaLogo() }}" alt="">
                @endif
                <div class="nombre">{{ $negocio->nombre }}</div>
                <div>NIT {{ $negocio->nit }}@if($negocio->regimen), {{ $negocio->regimen }}@endif</div>
                <div class="gris">{{ $negocio->direccion }}@if($negocio->barrioTexto()), {{ $negocio->barrioTexto() }}@endif</div>
                @if($negocio->ciudad)<div class="gris">{{ $negocio->ciudad }}</div>@endif
                @if($negocio->telefono)<div class="gris">Tel. {{ $negocio->telefono }}</div>@endif
                @if($negocio->resolucion_dian)<div class="gris">{{ $negocio->resolucion_dian }}</div>@endif
            </td>
            <td class="derecha">
                <div class="numero">Venta #{{ $venta->id }}</div>
                <div>{{ \Carbon\Carbon::parse($venta->created_at)->format('d/m/Y h:i a') }}</div>
                <div class="gris">Cliente: {{ optional($venta->cliente)->nombre ?? 'Cliente eliminado' }}</div>
                <div class="gris">Vendido por: {{ optional($venta->usuario)->name ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th>Producto</th><th>Código</th><th class="num">Cantidad</th><th class="num">Precio</th><th class="num">Subtotal</th></tr>
        </thead>
        <tbody>
            @foreach($venta->productos as $producto)
                <tr>
                    <td>{{ $producto->descripcion }}</td>
                    <td class="gris">{{ $producto->codigo_barras }}</td>
                    <td class="num">{{ cantidad($producto->cantidad, 3) }} {{ strtolower($producto->unidad) }}</td>
                    <td class="num">{{ dinero($producto->precio) }}</td>
                    <td class="num">{{ dinero($producto->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- dompdf no respeta márgenes en tablas: la columna vacía empuja los totales a la derecha --}}
    <table class="totales">
        @if($domicilio > 0)
            <tr><td class="hueco"></td><td>Subtotal</td><td class="derecha">{{ dinero($subtotal) }}</td></tr>
            <tr><td class="hueco"></td><td>Domicilio</td><td class="derecha">{{ dinero($domicilio) }}</td></tr>
        @endif
        <tr class="total"><td class="hueco"></td><td>Total</td><td class="derecha">{{ dinero($total) }}</td></tr>
        <tr><td class="hueco"></td><td class="gris">Pagó</td><td class="derecha gris">{{ dinero($venta->total_dinero) }}</td></tr>
        @if($venta->total_fiado > 0)
            <tr><td class="hueco"></td><td class="gris">Quedó fiado</td><td class="derecha gris">{{ dinero($venta->total_fiado) }}</td></tr>
        @else
            <tr><td class="hueco"></td><td class="gris">Cambio</td><td class="derecha gris">{{ dinero(max(0, $venta->total_vueltos)) }}</td></tr>
        @endif
    </table>

    <div class="pie">
        @if($negocio->mensaje_pie){{ $negocio->mensaje_pie }}<br>@endif
        {{ $negocio->firma_software }}
    </div>
</body>
</html>
