@extends('maestra')
@section("titulo", "Inicio")
@section('contenido')
@php
    $accesos = [
        ["ruta" => route("productos.index"), "icono" => "fa-shopping-basket", "titulo" => "Productos", "texto" => "Precios, existencias y códigos"],
        ["ruta" => route("ventas.index"), "icono" => "fa-receipt", "titulo" => "Ventas", "texto" => "Historial y reimpresión de tickets"],
        ["ruta" => route("compras.index"), "icono" => "fa-truck-loading", "titulo" => "Compras", "texto" => "Lo que le compras a proveedores"],
        ["ruta" => route("clientes.index"), "icono" => "fa-user-friends", "titulo" => "Clientes", "texto" => "Nombres y teléfonos"],
        ["ruta" => route("usuarios.deudores"), "icono" => "fa-book", "titulo" => "Fiados", "texto" => "Quién debe y registrar abonos"],
        ["ruta" => route("productos.alert"), "icono" => "fa-exclamation-triangle", "titulo" => "Stock bajo", "texto" => "Productos por reponer"],
        ["ruta" => route("usuarios.index"), "icono" => "fa-id-badge", "titulo" => "Usuarios", "texto" => "Cajas y contraseñas"],
    ];
    if ($esAdmin) {
        array_unshift($accesos, ["ruta" => route("tablero.index"), "icono" => "fa-chart-bar", "titulo" => "Tablero", "texto" => "Métricas del negocio"]);
        $accesos[] = ["ruta" => route("cajas.index"), "icono" => "fa-cash-register", "titulo" => "Cajas", "texto" => "Ventas de cada caja"];
    }
@endphp

<div class="inicio">
    <div class="inicio-encabezado">
        <div>
            <h1 class="titulo-pagina">{{ $saludo }}, {{ Auth::user()->name }}</h1>
            <p class="subtitulo-pagina">{{ ucfirst($fechaLarga) }}</p>
        </div>
        <a class="btn btn-mazorca inicio-vender" href="{{ route('vender.index') }}">
            <i class="fas fa-cash-register" aria-hidden="true"></i> Vender
        </a>
    </div>

    {{-- Cifras: una sola franja separada por líneas, no tarjetas sueltas --}}
    <section class="inicio-cifras" aria-label="Resumen">
        <div class="inicio-cifra principal">
            <span class="etiqueta">Vendido hoy en tu caja</span>
            <span class="valor cifra">{{ dinero($vendidoHoy) }}</span>
            <span class="detalle">{{ $ventasHoy }} {{ $ventasHoy == 1 ? 'venta' : 'ventas' }}</span>
        </div>
        <div class="inicio-cifra">
            <span class="etiqueta">Este mes</span>
            <span class="valor cifra">{{ dinero($vendidoMes) }}</span>
            <span class="detalle">{{ ucfirst(now()->locale('es')->isoFormat('MMMM')) }}</span>
        </div>
        <a class="inicio-cifra enlace" href="{{ route('usuarios.deudores') }}">
            <span class="etiqueta">Fiado por cobrar</span>
            <span class="valor cifra">{{ dinero($fiadoPendiente) }}</span>
            <span class="detalle">De todas las cajas</span>
        </a>
        <a class="inicio-cifra enlace {{ $productosEnAlerta > 0 ? 'con-alerta' : '' }}" href="{{ route('productos.alert') }}">
            <span class="etiqueta">Stock bajo</span>
            <span class="valor cifra">{{ number_format($productosEnAlerta, 0, ',', '.') }}</span>
            <span class="detalle">Con menos de {{ \App\Producto::UMBRAL_ALERTA }} unidades</span>
        </a>
    </section>

    <div class="inicio-cuerpo">
        <section class="inicio-accesos" aria-labelledby="titulo-accesos">
            <h2 id="titulo-accesos" class="inicio-seccion">Ir a</h2>
            <div class="accesos">
                @foreach($accesos as $acceso)
                    <a class="acceso" href="{{ $acceso['ruta'] }}">
                        <i class="fas {{ $acceso['icono'] }}" aria-hidden="true"></i>
                        <span>
                            <strong>{{ $acceso['titulo'] }}</strong>
                            <small>{{ $acceso['texto'] }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="inicio-ultimas" aria-labelledby="titulo-ultimas">
            <div class="inicio-seccion-fila">
                <h2 id="titulo-ultimas" class="inicio-seccion">Últimas ventas de tu caja</h2>
                <a href="{{ route('ventas.index') }}">Ver todas</a>
            </div>
            <div class="panel p-0">
                @forelse($ultimasVentas as $venta)
                    <a class="ultima-venta" href="{{ route('ventas.show', $venta->id) }}">
                        <span class="ultima-hora cifra">
                            {{ \Carbon\Carbon::parse($venta->created_at)->format('d/m') }}
                            <small>{{ \Carbon\Carbon::parse($venta->created_at)->format('h:i a') }}</small>
                        </span>
                        <span class="ultima-cliente">
                            {{ $venta->cliente }}
                            @if($venta->total_fiado > 0)
                                <span class="badge badge-warning">Fiado</span>
                            @endif
                        </span>
                        <span class="ultima-total cifra">{{ dinero($venta->total_pagar) }}</span>
                    </a>
                @empty
                    <div class="inicio-vacio">
                        Aún no hay ventas en tu caja.
                        <a href="{{ route('vender.index') }}">Ir a Vender</a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
