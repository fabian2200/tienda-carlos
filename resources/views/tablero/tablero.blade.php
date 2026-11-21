@extends('maestra')
@section("titulo", "Tablero")
@section('contenido')
@php
    $r = $d->rango;
    $mismoDia = $r->desde->equalTo($r->hasta);
    $rangoTexto = $mismoDia
        ? ucfirst($r->desde->locale('es')->isoFormat('dddd D [de] MMMM'))
        : $r->desde->locale('es')->isoFormat('D [de] MMM') . ' – ' . $r->hasta->locale('es')->isoFormat('D [de] MMM YYYY');

    // Cambio frente al período anterior: flecha + porcentaje + texto (nunca solo color)
    $cambio = function ($valor, $subirEsBueno = true) use ($r) {
        if ($valor === null) {
            return '<span class="cambio neutro">Sin datos de ' . e($r->comparado) . '</span>';
        }
        $pct = round($valor * 100);
        if ($pct == 0) {
            return '<span class="cambio neutro"><i class="fas fa-equals" aria-hidden="true"></i> Igual que ' . e($r->comparado) . '</span>';
        }
        $bueno = ($pct > 0) === $subirEsBueno;
        $icono = $pct > 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        $texto = ($pct > 0 ? '+' : '−') . number_format(abs($pct), 0, ',', '.') . '%';
        return '<span class="cambio ' . ($bueno ? 'bueno' : 'malo') . '"><i class="fas ' . $icono . '" aria-hidden="true"></i> ' . $texto
            . ' <span class="cambio-vs">vs ' . e($r->comparado) . '</span></span>';
    };

    $puntos = $d->serie->puntos;
    $mejor = null;
    foreach ($puntos as $i => $p) {
        if ($p['total'] > 0 && ($mejor === null || $p['total'] > $puntos[$mejor]['total'])) { $mejor = $i; }
    }
    $horaPico = null;
    foreach ($d->horas as $i => $h) {
        if ($horaPico === null || $h['numero'] > $d->horas[$horaPico]['numero']) { $horaPico = $i; }
    }
    $maxProducto = $d->topProductos->max('total') ?: 1;
    $maxCategoria = $d->categorias->max('total') ?: 1;
@endphp

<div class="tablero">
    <header class="tablero-encabezado">
        <div>
            <h1 class="titulo-pagina">Tablero</h1>
            <p class="subtitulo-pagina">{{ $rangoTexto }} · todas las cajas</p>
        </div>
        <nav class="tablero-periodos" aria-label="Período">
            @foreach($periodos as $clave => $nombre)
                <a href="{{ route('tablero.index', ['periodo' => $clave]) }}" class="{{ $d->periodo === $clave ? 'activo' : '' }}"
                   @if($d->periodo === $clave) aria-current="page" @endif>{{ $nombre }}</a>
            @endforeach
        </nav>
    </header>

    {{-- Cifras principales --}}
    <section class="tablero-cifras" aria-label="Cifras del período">
        <div class="tablero-cifra principal">
            <span class="etiqueta">Vendido</span>
            <span class="valor">{{ dinero($d->ventas->valor) }}</span>
            {!! $cambio($d->ventas->cambio) !!}
        </div>
        <div class="tablero-cifra">
            <span class="etiqueta">Ganancia estimada</span>
            <span class="valor">{{ dinero($d->ganancia->valor) }}</span>
            <span class="detalle">
                @if($d->ganancia->margen !== null)
                    Margen de {{ number_format($d->ganancia->margen * 100, 1, ',', '.') }}%
                @else
                    Sin ventas con costo
                @endif
            </span>
        </div>
        <div class="tablero-cifra">
            <span class="etiqueta">Ventas</span>
            <span class="valor">{{ number_format($d->numero->valor, 0, ',', '.') }}</span>
            {!! $cambio($d->numero->cambio) !!}
        </div>
        <div class="tablero-cifra">
            <span class="etiqueta">Venta promedio</span>
            <span class="valor">{{ dinero($d->promedio->valor) }}</span>
            {!! $cambio($d->promedio->cambio) !!}
        </div>
    </section>

    <div class="tablero-rejilla">
        {{-- Ventas en el tiempo --}}
        <section class="panel tablero-panel ancho-2" aria-labelledby="t-serie">
            <div class="tablero-panel-cabeza">
                <h2 id="t-serie">{{ $d->serie->porHora ? 'Ventas por hora' : 'Ventas por día' }}</h2>
                @if($mejor !== null)
                    <span class="tablero-nota">{{ $d->serie->porHora ? 'Mejor hora' : 'Mejor día' }}: <strong>{{ $puntos[$mejor]['detalle'] }}</strong></span>
                @endif
            </div>
            @if($d->ventas->valor > 0)
                @include('tablero._columnas', [
                    'puntos' => $puntos, 'campo' => 'total', 'tipo' => 'dinero', 'alto' => 230,
                    'titulo' => ($d->serie->porHora ? 'Ventas por hora' : 'Ventas por día') . ' del período',
                    'resaltar' => $mejor,
                    'tip' => fn($p) => $p['detalle'] . '|' . dinero($p['total']) . '|' . $p['numero'] . ($p['numero'] == 1 ? ' venta' : ' ventas'),
                ])
                <details class="tablero-tabla">
                    <summary>Ver como tabla</summary>
                    <table class="table table-sm">
                        <thead><tr><th>{{ $d->serie->porHora ? 'Hora' : 'Día' }}</th><th class="num">Ventas</th><th class="num">Vendido</th></tr></thead>
                        <tbody>
                            @foreach($puntos as $p)
                                <tr><td>{{ $p['detalle'] }}</td><td class="num">{{ $p['numero'] }}</td><td class="num">{{ dinero($p['total']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </details>
            @else
                <p class="tablero-vacio">No hay ventas en este período.</p>
            @endif
        </section>

        {{-- Fiados --}}
        <section class="panel tablero-panel" aria-labelledby="t-fiados">
            <div class="tablero-panel-cabeza">
                <h2 id="t-fiados">Fiados</h2>
                <a href="{{ route('usuarios.deudores') }}">Ver todos</a>
            </div>
            <div class="tablero-dato">
                <span class="etiqueta">Por cobrar hoy</span>
                <span class="valor">{{ dinero($d->porCobrar) }}</span>
                <span class="detalle">Se fiaron {{ dinero($d->fiadoPeriodo) }} en el período</span>
            </div>
            @if($d->deudores->count())
                <h3 class="tablero-subtitulo">Los que más deben</h3>
                <ol class="tablero-lista">
                    @foreach($d->deudores as $c)
                        <li>
                            <a href="{{ route('usuarios.infoDeuda', ['id_cliente' => $c->id]) }}">{{ $c->nombre }}</a>
                            <span class="num">{{ dinero($c->total_deuda) }}</span>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="tablero-vacio">Nadie debe en este momento.</p>
            @endif
        </section>

        {{-- Productos más vendidos --}}
        <section class="panel tablero-panel" aria-labelledby="t-top">
            <div class="tablero-panel-cabeza">
                <h2 id="t-top">Lo que más se vende</h2>
            </div>
            @forelse($d->topProductos as $p)
                <div class="barra-fila" tabindex="0" data-tip="{{ $p->descripcion }}|{{ dinero($p->total) }}|{{ cantidad($p->cantidad) }} {{ strtolower($p->unidad ?: 'und') }}">
                    <span class="barra-nombre" title="{{ $p->descripcion }}">{{ $p->descripcion }}</span>
                    <span class="barra-pista"><span class="barra-relleno" style="width: {{ max(1, $p->total / $maxProducto * 100) }}%"></span></span>
                    <span class="barra-valor">{{ dinero_corto($p->total) }}</span>
                </div>
            @empty
                <p class="tablero-vacio">Sin ventas en este período.</p>
            @endforelse
        </section>

        {{-- Categorías --}}
        <section class="panel tablero-panel" aria-labelledby="t-cat">
            <div class="tablero-panel-cabeza">
                <h2 id="t-cat">Por categoría</h2>
            </div>
            @forelse($d->categorias as $c)
                <div class="barra-fila" tabindex="0" data-tip="{{ $c->categoria }}|{{ dinero($c->total) }}|{{ number_format($c->parte * 100, 1, ',', '.') }}% de lo vendido">
                    <span class="barra-nombre">{{ $c->categoria }}</span>
                    <span class="barra-pista"><span class="barra-relleno" style="width: {{ max(1, $c->total / $maxCategoria * 100) }}%"></span></span>
                    <span class="barra-valor">{{ number_format($c->parte * 100, 0, ',', '.') }}%</span>
                </div>
            @empty
                <p class="tablero-vacio">Sin ventas en este período.</p>
            @endforelse
        </section>

        {{-- Horas pico --}}
        <section class="panel tablero-panel" aria-labelledby="t-horas">
            <div class="tablero-panel-cabeza">
                <h2 id="t-horas">¿A qué hora se vende más?</h2>
            </div>
            @if(count($d->horas))
                <p class="tablero-nota">Hora pico: <strong>{{ $d->horas[$horaPico]['etiqueta'] }}</strong>, con {{ number_format($d->horas[$horaPico]['numero'], 0, ',', '.') }} ventas</p>
                @include('tablero._columnas', [
                    'puntos' => $d->horas, 'campo' => 'numero', 'tipo' => 'numero', 'alto' => 170, 'ancho' => 380, 'minimo' => 0,
                    'titulo' => 'Número de ventas por hora del día',
                    'resaltar' => $horaPico,
                    'tip' => fn($h) => $h['etiqueta'] . '|' . number_format($h['numero'], 0, ',', '.') . ' ventas',
                ])
            @else
                <p class="tablero-vacio">Sin ventas en este período.</p>
            @endif
        </section>

        {{-- Por agotarse --}}
        <section class="panel tablero-panel ancho-2" aria-labelledby="t-agotar">
            <div class="tablero-panel-cabeza">
                <h2 id="t-agotar">Se van a acabar</h2>
                <a href="{{ route('productos.alert') }}">Stock bajo ({{ number_format($d->inventario->stockBajo, 0, ',', '.') }})</a>
            </div>
            @forelse($d->porAgotarse as $p)
                <div class="agotar-fila">
                    <span class="agotar-nombre">{{ $p->descripcion }}</span>
                    <span class="agotar-dato">
                        @if($p->existencia <= 0)
                            <span class="estado critico"><i class="fas fa-times-circle" aria-hidden="true"></i> Agotado</span>
                        @else
                            @php
                                $dias = $p->dias === null ? '' : ($p->dias < 1 ? 'menos de 1 día' : (floor($p->dias) == 1 ? 'alcanza 1 día' : 'alcanza ' . number_format(floor($p->dias), 0, ',', '.') . ' días'));
                            @endphp
                            <span class="estado {{ $p->dias !== null && $p->dias < 3 ? 'alerta' : '' }}">
                                @if($p->dias !== null && $p->dias < 3)<i class="fas fa-exclamation-circle" aria-hidden="true"></i>@endif
                                Quedan {{ cantidad($p->existencia) }}{{ $dias ? ' · ' . $dias : '' }}
                            </span>
                        @endif
                    </span>
                </div>
            @empty
                <p class="tablero-vacio">Ningún producto con stock bajo.</p>
            @endforelse
            <p class="tablero-pie">Ordenados por lo que más se vende. Los días son un cálculo con las ventas de los últimos 30 días.</p>
        </section>

        {{-- Inventario --}}
        <section class="panel tablero-panel" aria-labelledby="t-inv">
            <div class="tablero-panel-cabeza">
                <h2 id="t-inv">Inventario</h2>
                <a href="{{ route('productos.index') }}">Productos</a>
            </div>
            <div class="tablero-dato">
                <span class="etiqueta">Lo que costó la mercancía en bodega</span>
                <span class="valor">{{ dinero($d->inventario->costo) }}</span>
            </div>
            <div class="tablero-dato">
                <span class="etiqueta">Si se vende todo a precio actual</span>
                <span class="valor secundario">{{ dinero($d->inventario->venta) }}</span>
                <span class="detalle">Ganancia posible: {{ dinero($d->inventario->venta - $d->inventario->costo) }}</span>
            </div>
            @if($d->inventario->excluidos > 0)
                <p class="tablero-pie">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    No incluye {{ number_format($d->inventario->excluidos, 0, ',', '.') }} productos con {{ number_format(\App\Services\TableroService::EXISTENCIA_IRREAL, 0, ',', '.') }} unidades o más:
                    parecen existencias sin actualizar.
                </p>
            @endif
        </section>
    </div>

    <p class="tablero-pie">
        La ganancia es estimada: se calcula con el precio de compra actual de cada producto, no con el de la fecha de la venta.
    </p>
</div>

<div class="tablero-tip" id="tableroTip" role="tooltip" hidden></div>

<script>
// Recuadro con el detalle al pasar el mouse o enfocar con el teclado (columnas y barras)
(function () {
    const tip = document.getElementById("tableroTip");
    const mostrar = (el, x, y) => {
        const partes = el.dataset.tip.split("|");
        tip.innerHTML = "<strong></strong>" + partes.slice(1).map(() => "<span></span>").join("");
        tip.firstChild.textContent = partes[0];
        partes.slice(1).forEach((t, i) => (tip.children[i + 1].textContent = t));
        tip.hidden = false;
        const ancho = tip.offsetWidth, alto = tip.offsetHeight;
        const izq = Math.min(Math.max(8, x - ancho / 2), window.innerWidth - ancho - 8);
        const arriba = y - alto - 14 < 8 ? y + 18 : y - alto - 14;
        tip.style.left = izq + "px";
        tip.style.top = arriba + "px";
    };
    const ocultar = () => { tip.hidden = true; document.querySelectorAll(".tip-activo").forEach(e => e.classList.remove("tip-activo")); };
    document.querySelectorAll("[data-tip]").forEach(el => {
        el.addEventListener("mousemove", e => { el.classList.add("tip-activo"); mostrar(el, e.clientX, e.clientY); });
        el.addEventListener("mouseleave", ocultar);
        el.addEventListener("focus", () => { const b = el.getBoundingClientRect(); el.classList.add("tip-activo"); mostrar(el, b.left + b.width / 2, b.top); });
        el.addEventListener("blur", ocultar);
    });
    window.addEventListener("scroll", ocultar, { passive: true });
})();
</script>
@endsection
