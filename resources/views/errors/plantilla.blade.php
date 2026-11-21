{{-- Plantilla de páginas de error: independiente de maestra (debe funcionar aunque falle la base de datos). --}}
@php
    try { $nombreNegocio = \App\Negocio::actual()->nombre; } catch (\Throwable $e) { $nombreNegocio = config('app.name'); }
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') - {{ $nombreNegocio }}</title>
    <link href="{{ url('/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ url('/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ url('/css/pos.css') }}" rel="stylesheet">
</head>
<body class="sin-menu">
    <main class="pagina-error">
        <div class="pagina-error-caja">
            <span class="pagina-error-icono" aria-hidden="true"><i class="fas @yield('icono', 'fa-exclamation-circle')"></i></span>
            <h1 class="titulo-pagina">@yield('titulo')</h1>
            <p class="subtitulo-pagina">@yield('mensaje')</p>
            <div class="pagina-error-acciones">
                @yield('acciones')
                <a class="btn btn-primary" href="{{ url('/home') }}">Ir al inicio</a>
            </div>
            <p class="pagina-error-negocio">{{ $nombreNegocio }}</p>
        </div>
    </main>
</body>
</html>
