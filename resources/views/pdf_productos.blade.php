<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Inventario de productos' }}</title>
    <style>
        /* dompdf: estilos sencillos y fuente DejaVu (trae tildes y ñ) */
        * { font-family: "DejaVu Sans", sans-serif; }
        body { margin: 0; color: #1D2622; font-size: 10px; }
        .cabecera { width: 100%; border-bottom: 2px solid #1E5B45; padding-bottom: 10px; margin-bottom: 12px; }
        .cabecera td { vertical-align: top; }
        .nombre { font-size: 16px; font-weight: bold; color: #143F30; }
        .gris { color: #5B6862; }
        .derecha { text-align: right; }
        .logo { height: 50px; }
        .titulo { font-size: 15px; font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { background: #E3EEE8; color: #143F30; text-align: left; padding: 5px 6px; font-size: 9px; }
        table.items td { padding: 5px 6px; border-bottom: 1px solid #DCE2DE; }
        table.items .num { text-align: right; white-space: nowrap; }
        .bajo { color: #8A5D0B; }
        .agotado { color: #B3343F; font-weight: bold; }
        table.total { width: 100%; margin-top: 12px; border-collapse: collapse; }
        table.total td { padding: 8px 6px; font-size: 14px; font-weight: bold; border-top: 2px solid #1E5B45; }
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
                <div>NIT {{ $negocio->nit }}</div>
                <div class="gris">{{ $negocio->direccion }}@if($negocio->barrioTexto()), {{ $negocio->barrioTexto() }}@endif</div>
                @if($negocio->telefono)<div class="gris">Tel. {{ $negocio->telefono }}</div>@endif
            </td>
            <td class="derecha">
                <div class="titulo">{{ $titulo ?? 'Inventario de productos' }}</div>
                <div>{{ now()->format('d/m/Y h:i a') }}</div>
                <div class="gris">{{ number_format(count($productos), 0, ',', '.') }} productos</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="num">Precio de venta</th>
                <th class="num">Ganancia</th>
                <th class="num">Existencia</th>
                <th class="num">Valor en inventario</th>
            </tr>
        </thead>
        <tbody>
        @foreach($productos as $producto)
            @php $clase = $producto->existencia <= 0 ? 'agotado' : ($producto->existencia < \App\Producto::UMBRAL_ALERTA ? 'bajo' : ''); @endphp
            <tr>
                <td class="gris">{{ $producto->codigo_barras }}</td>
                <td>{{ $producto->descripcion }}</td>
                <td class="num">{{ dinero($producto->precio_venta) }}</td>
                <td class="num">{{ dinero($producto->precio_venta - $producto->precio_compra) }}</td>
                <td class="num {{ $clase }}">{{ cantidad($producto->existencia) }} {{ $producto->unidad_medida === 'Kilos' ? 'kg' : ($producto->unidad_medida === 'Libras' ? 'lb' : 'und') }}</td>
                <td class="num">{{ dinero($producto->total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="total">
        <tr><td>Valor total en inventario (a precio de venta)</td><td class="derecha">{{ dinero($total) }}</td></tr>
    </table>
</body>
</html>
