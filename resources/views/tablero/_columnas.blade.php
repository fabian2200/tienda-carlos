{{--
    Gráfica de columnas en SVG (una sola serie), sin librerías.
    Parámetros:
      $puntos   arreglo de ['etiqueta', 'detalle'?, <campo>, ...]
      $campo    clave del valor a graficar
      $tipo     'dinero' | 'numero'
      $titulo   nombre accesible de la gráfica
      $alto     alto del área de dibujo (por defecto 220)
      $resaltar índice de la columna a resaltar (opcional)
      $tip      función (punto) => texto del recuadro al pasar el mouse
--}}
@php
    $alto = $alto ?? 220;
    $ancho = $ancho ?? 760;      // ancho del dibujo (la gráfica se escala al panel)
    $minimo = $minimo ?? 520;    // ancho mínimo en pantalla; si el panel es más angosto, se desliza
    $margenIzq = 72; $margenDer = 8; $margenSup = 22; $margenInf = 26;
    $plotAncho = $ancho - $margenIzq - $margenDer;
    $plotAlto = $alto;
    $total = count($puntos);
    $valores = array_map(fn($p) => (float) $p[$campo], $puntos);
    $maximo = max($valores ?: [0]);

    // Escala con números redondos (1, 2, 2,5 o 5 × 10^n) y entre 2 y 5 divisiones,
    // la más ajustada al valor más alto
    $paso = 1; $divisiones = 4;
    if ($maximo > 0) {
        $bruto = $maximo / 5;
        $potencia = pow(10, floor(log10($bruto)));
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($m * $potencia >= $bruto) { $paso = $m * $potencia; break; }
        }
        if ($tipo === 'numero' || $paso < 1) { $paso = max(1, ceil($paso)); }
        $divisiones = max(2, (int) ceil($maximo / $paso));
    }
    $tope = $paso * $divisiones;
    $y = fn($v) => $margenSup + $plotAlto - ($tope > 0 ? $v / $tope * $plotAlto : 0);
    $formato = fn($v) => $tipo === 'dinero' ? dinero_corto($v) : number_format($v, 0, ',', '.');

    $ranura = $total > 0 ? $plotAncho / $total : $plotAncho;
    $grosor = min(24, max(3, $ranura - max(2, $ranura * 0.28)));
    // Etiquetas del eje X sin que se monten: una de cada N
    $cadaN = max(1, (int) ceil($total / max(1, floor($plotAncho / 46))));
    $resaltar = $resaltar ?? null;
@endphp
<div class="grafica" data-grafica>
    <svg viewBox="0 0 {{ $ancho }} {{ $margenSup + $plotAlto + $margenInf }}" role="img" aria-label="{{ $titulo }}" style="min-width: {{ $minimo }}px">
        {{-- Líneas guía y valores del eje --}}
        @for($i = 0; $i <= $divisiones; $i++)
            @php $yy = $y($paso * $i); @endphp
            <line class="guia {{ $i === 0 ? 'base' : '' }}" x1="{{ $margenIzq }}" x2="{{ $ancho - $margenDer }}" y1="{{ $yy }}" y2="{{ $yy }}"/>
            <text class="eje" x="{{ $margenIzq - 8 }}" y="{{ $yy + 4 }}" text-anchor="end">{{ $formato($paso * $i) }}</text>
        @endfor

        @foreach($puntos as $i => $p)
            @php
                $v = (float) $p[$campo];
                $cx = $margenIzq + $ranura * $i + $ranura / 2;
                $x0 = $cx - $grosor / 2;
                $yTop = $y($v);
                $h = $margenSup + $plotAlto - $yTop;
                $r = min(4, $h, $grosor / 2);
            @endphp
            <g class="columna {{ $resaltar === $i ? 'resaltada' : '' }}" tabindex="0" data-tip="{{ $tip($p) }}">
                {{-- Zona sensible: toda la ranura, más grande que la barra --}}
                <rect class="zona" x="{{ $margenIzq + $ranura * $i }}" y="{{ $margenSup }}" width="{{ $ranura }}" height="{{ $plotAlto }}"/>
                @if($h > 0.5)
                    <path class="barra" d="M{{ $x0 }},{{ $margenSup + $plotAlto }} V{{ $yTop + $r }} Q{{ $x0 }},{{ $yTop }} {{ $x0 + $r }},{{ $yTop }} H{{ $x0 + $grosor - $r }} Q{{ $x0 + $grosor }},{{ $yTop }} {{ $x0 + $grosor }},{{ $yTop + $r }} V{{ $margenSup + $plotAlto }} Z"/>
                @endif
                @if($resaltar === $i && $v > 0)
                    <text class="valor-tope" x="{{ $cx }}" y="{{ $yTop - 7 }}" text-anchor="middle">{{ $formato($v) }}</text>
                @endif
            </g>
            @if($i % $cadaN === 0)
                <text class="eje" x="{{ $cx }}" y="{{ $margenSup + $plotAlto + 18 }}" text-anchor="middle">{{ $p['etiqueta'] }}</text>
            @endif
        @endforeach
    </svg>
</div>
