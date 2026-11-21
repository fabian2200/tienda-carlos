@extends("maestra")
@section("titulo", "Cajas")
@section("contenido")
@php $maxMes = max(1, $cajas->max('total_ventas_mensual')); @endphp
<div class="pagina pagina-media">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">Cajas</h1>
            <p class="subtitulo-pagina">Ventas de todas las cajas, {{ now()->locale('es')->isoFormat('MMMM [de] YYYY') }}</p>
        </div>
    </div>

    <section class="inicio-cifras" aria-label="Resumen de todas las cajas">
        <div class="inicio-cifra principal">
            <span class="etiqueta">Vendido hoy</span>
            <span class="valor cifra">{{ dinero($totalVendidoHoy) }}</span>
            <span class="detalle">{{ $cajas->sum('ventas_hoy') }} ventas</span>
        </div>
        <div class="inicio-cifra">
            <span class="etiqueta">Este mes</span>
            <span class="valor cifra">{{ dinero($totalVendidoMensual) }}</span>
        </div>
        <a class="inicio-cifra enlace" href="{{ route('usuarios.deudores') }}">
            <span class="etiqueta">Por cobrar</span>
            <span class="valor cifra">{{ dinero($totalPorCobrar) }}</span>
            <span class="detalle">Fiado menos abonos</span>
        </a>
        <div class="inicio-cifra">
            <span class="etiqueta">Abonado</span>
            <span class="valor cifra">{{ dinero($totalAbonado) }}</span>
            <span class="detalle">De {{ dinero($totalFiado) }} fiado</span>
        </div>
    </section>

    <div class="panel p-0 tabla-contenedor mt-4">
        <table class="tabla-pos">
            <thead>
                <tr>
                    <th>Caja</th>
                    <th class="num">Hoy</th>
                    <th>Este mes</th>
                    <th class="num">Fiado en esta caja</th>
                </tr>
            </thead>
            <tbody>
            @foreach($cajas as $caja)
                <tr>
                    <td>
                        <div class="producto-celda">
                            <span class="barra-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($caja->name, 0, 1)) }}</span>
                            <span class="producto-texto"><strong>{{ $caja->name }}</strong><small>{{ $caja->email }}</small></span>
                        </div>
                    </td>
                    <td class="num">
                        <strong>{{ dinero($caja->total_ventas_hoy) }}</strong>
                        <small class="d-block text-muted">{{ $caja->ventas_hoy }} ventas</small>
                    </td>
                    <td class="celda-barra">
                        <span class="cifra"><strong>{{ dinero($caja->total_ventas_mensual) }}</strong></span>
                        <span class="barra-proporcion" aria-hidden="true"><span style="width: {{ round($caja->total_ventas_mensual / $maxMes * 100) }}%"></span></span>
                    </td>
                    <td class="num">{{ dinero($caja->total_fiado) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
