<?php

namespace App\Services;

use App\Producto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Métricas del tablero (todas las cajas). Todo se calcula en SQL por período.
 * ventas.fecha_venta se guarda como texto "Y-m-d": comparar como texto sirve para rangos.
 */
class TableroService
{
    /** Períodos que se pueden elegir: clave => nombre */
    const PERIODOS = [
        "hoy" => "Hoy",
        "7d" => "7 días",
        "30d" => "30 días",
        "mes" => "Este mes",
        "mes_pasado" => "Mes pasado",
        "90d" => "90 días",
    ];

    /** Rango actual y el anterior de la misma duración, para comparar. */
    public function rango($periodo)
    {
        $hoy = Carbon::today();
        switch ($periodo) {
            case "hoy":
                $desde = $hoy->copy(); $hasta = $hoy->copy();
                $antDesde = $hoy->copy()->subDay(); $antHasta = $antDesde->copy();
                $comparado = "ayer a esta hora";
                break;
            case "7d":
                $desde = $hoy->copy()->subDays(6); $hasta = $hoy->copy();
                $antDesde = $desde->copy()->subDays(7); $antHasta = $desde->copy()->subDay();
                $comparado = "los 7 días anteriores";
                break;
            case "mes":
                $desde = $hoy->copy()->startOfMonth(); $hasta = $hoy->copy();
                // Mismos días del mes anterior (1 al N), para comparar parejo
                $antDesde = $desde->copy()->subMonthNoOverflow();
                $antHasta = $antDesde->copy()->addDays(min($hoy->day, $antDesde->daysInMonth) - 1);
                $comparado = "los mismos días del mes pasado";
                break;
            case "mes_pasado":
                $desde = $hoy->copy()->subMonthNoOverflow()->startOfMonth(); $hasta = $desde->copy()->endOfMonth()->startOfDay();
                $antDesde = $desde->copy()->subMonthNoOverflow(); $antHasta = $antDesde->copy()->endOfMonth()->startOfDay();
                $comparado = "el mes anterior";
                break;
            case "90d":
                $desde = $hoy->copy()->subDays(89); $hasta = $hoy->copy();
                $antDesde = $desde->copy()->subDays(90); $antHasta = $desde->copy()->subDay();
                $comparado = "los 90 días anteriores";
                break;
            default: // 30d
                $desde = $hoy->copy()->subDays(29); $hasta = $hoy->copy();
                $antDesde = $desde->copy()->subDays(30); $antHasta = $desde->copy()->subDay();
                $comparado = "los 30 días anteriores";
        }
        return (object) compact("desde", "hasta", "antDesde", "antHasta", "comparado");
    }

    private function ventasEntre(Carbon $desde, Carbon $hasta)
    {
        return DB::table("ventas")->whereBetween("ventas.fecha_venta", [$desde->format("Y-m-d"), $hasta->format("Y-m-d")]);
    }

    /**
     * Total vendido, número de ventas y lo que quedó fiado en un rango.
     * Con $horaTope ("H:i:s") solo cuenta lo vendido hasta esa hora (para comparar hoy con ayer a la misma hora).
     */
    private function totales(Carbon $desde, Carbon $hasta, $horaTope = null)
    {
        $r = $this->ventasEntre($desde, $hasta)
            ->when($horaTope, function ($q) use ($horaTope) { $q->whereRaw("TIME(ventas.created_at) <= ?", [$horaTope]); })
            ->selectRaw("COALESCE(SUM(total_pagar), 0) AS total, COUNT(*) AS numero, COALESCE(SUM(total_fiado), 0) AS fiado")
            ->first();
        return (object) ["total" => (float) $r->total, "numero" => (int) $r->numero, "fiado" => (float) $r->fiado];
    }

    /** Ganancia estimada con el precio de compra actual de cada producto. */
    private function ganancia(Carbon $desde, Carbon $hasta, $horaTope = null)
    {
        $r = $this->ventasEntre($desde, $hasta)
            ->when($horaTope, function ($q) use ($horaTope) { $q->whereRaw("TIME(ventas.created_at) <= ?", [$horaTope]); })
            ->join("productos_vendidos as pv", "pv.id_venta", "=", "ventas.id")
            ->join("productos as p", "p.codigo_barras", "=", "pv.codigo_barras")
            ->where("p.precio_compra", ">", 0)
            ->selectRaw("COALESCE(SUM(pv.cantidad * (pv.precio - p.precio_compra)), 0) AS ganancia, COALESCE(SUM(pv.cantidad * pv.precio), 0) AS vendido")
            ->first();
        $vendido = (float) $r->vendido;
        return (object) ["ganancia" => (float) $r->ganancia, "margen" => $vendido > 0 ? $r->ganancia / $vendido : null];
    }

    /** Cambio relativo entre dos valores (null si no hay base para comparar). */
    private function cambio($actual, $anterior)
    {
        return $anterior > 0 ? ($actual - $anterior) / $anterior : null;
    }

    /** Ventas por día del rango (o por hora si el rango es un solo día), con los días sin ventas en cero. */
    private function serie(Carbon $desde, Carbon $hasta)
    {
        if ($desde->equalTo($hasta)) {
            $filas = $this->ventasEntre($desde, $hasta)
                ->selectRaw("HOUR(created_at) AS clave, SUM(total_pagar) AS total, COUNT(*) AS numero")
                ->groupBy("clave")->get()->keyBy("clave");
            $puntos = [];
            $ahora = (int) date("G");
            $ultima = $desde->isToday() ? max($ahora, 6) : 21;
            $primera = min(5, $filas->keys()->min() ?? 5);
            for ($h = $primera; $h <= max($ultima, $filas->keys()->max() ?? 0); $h++) {
                $f = $filas->get($h);
                $puntos[] = [
                    "etiqueta" => Carbon::createFromTime($h)->format("g a"),
                    "detalle" => Carbon::createFromTime($h)->format("g") . " a " . Carbon::createFromTime(($h + 1) % 24)->format("g a"),
                    "total" => $f ? (float) $f->total : 0,
                    "numero" => $f ? (int) $f->numero : 0,
                ];
            }
            return (object) ["porHora" => true, "puntos" => $puntos];
        }

        $filas = $this->ventasEntre($desde, $hasta)
            ->selectRaw("fecha_venta AS clave, SUM(total_pagar) AS total, COUNT(*) AS numero")
            ->groupBy("clave")->get()->keyBy("clave");
        $puntos = [];
        for ($d = $desde->copy(); $d->lte($hasta); $d->addDay()) {
            $f = $filas->get($d->format("Y-m-d"));
            $puntos[] = [
                "etiqueta" => $d->format("j/n"),
                "detalle" => ucfirst($d->locale("es")->isoFormat("ddd D [de] MMM")),
                "total" => $f ? (float) $f->total : 0,
                "numero" => $f ? (int) $f->numero : 0,
                "domingo" => $d->isSunday(),
            ];
        }
        return (object) ["porHora" => false, "puntos" => $puntos];
    }

    /** Número de ventas por hora del día en el rango: a qué hora se vende más. */
    private function horasPico(Carbon $desde, Carbon $hasta)
    {
        $filas = $this->ventasEntre($desde, $hasta)
            ->selectRaw("HOUR(created_at) AS hora, COUNT(*) AS numero")
            ->groupBy("hora")->orderBy("hora")->get();
        if ($filas->isEmpty()) {
            return [];
        }
        $porHora = $filas->keyBy("hora");
        $puntos = [];
        for ($h = (int) $filas->min("hora"); $h <= (int) $filas->max("hora"); $h++) {
            $puntos[] = [
                "hora" => $h,
                "etiqueta" => Carbon::createFromTime($h)->format("g a"),
                "numero" => $porHora->has($h) ? (int) $porHora[$h]->numero : 0,
            ];
        }
        return $puntos;
    }

    private function productosVendidos(Carbon $desde, Carbon $hasta)
    {
        return $this->ventasEntre($desde, $hasta)->join("productos_vendidos as pv", "pv.id_venta", "=", "ventas.id");
    }

    /** Productos que más plata dejaron en el rango. */
    private function topProductos(Carbon $desde, Carbon $hasta, $limite = 8)
    {
        return $this->productosVendidos($desde, $hasta)
            ->selectRaw("pv.codigo_barras, MAX(pv.descripcion) AS descripcion, MAX(pv.unidad) AS unidad, SUM(pv.cantidad) AS cantidad, SUM(pv.cantidad * pv.precio) AS total")
            ->groupBy("pv.codigo_barras")
            ->orderByDesc("total")
            ->limit($limite)
            ->get();
    }

    /** Venta por categoría del producto (las 5 primeras y el resto en "Otras"). */
    private function categorias(Carbon $desde, Carbon $hasta)
    {
        $filas = $this->productosVendidos($desde, $hasta)
            ->leftJoin("productos as p", "p.codigo_barras", "=", "pv.codigo_barras")
            ->selectRaw("COALESCE(NULLIF(TRIM(p.categoria), ''), 'Sin categoría') AS categoria, SUM(pv.cantidad * pv.precio) AS total")
            ->groupBy("categoria")
            ->orderByDesc("total")
            ->get();
        $principales = $filas->take(5)->values();
        $resto = $filas->slice(5)->sum("total");
        if ($resto > 0) {
            $principales->push((object) ["categoria" => "Resto de categorías", "total" => $resto]);
        }
        $suma = $filas->sum("total");
        return $principales->map(function ($c) use ($suma) {
            $c->total = (float) $c->total;
            $c->parte = $suma > 0 ? $c->total / $suma : 0;
            return $c;
        });
    }

    /** Productos con poco stock, primero los que más se venden (los que se van a acabar antes). */
    private function porAgotarse($limite = 6)
    {
        $vendidos = DB::table("productos_vendidos as pv")
            ->join("ventas", "ventas.id", "=", "pv.id_venta")
            ->where("ventas.fecha_venta", ">=", Carbon::today()->subDays(29)->format("Y-m-d"))
            ->selectRaw("pv.codigo_barras, SUM(pv.cantidad) AS vendido")
            ->groupBy("pv.codigo_barras");

        return DB::table("productos as p")
            ->leftJoinSub($vendidos, "v", "v.codigo_barras", "=", "p.codigo_barras")
            ->where("p.existencia", "<", Producto::UMBRAL_ALERTA)
            ->selectRaw("p.id, p.descripcion, p.existencia, p.unidad_medida, COALESCE(v.vendido, 0) AS vendido_30d")
            ->orderByDesc("vendido_30d")
            ->orderBy("p.existencia")
            ->limit($limite)
            ->get()
            ->map(function ($p) {
                // Días que alcanza el stock al ritmo de los últimos 30 días
                $porDia = $p->vendido_30d / 30;
                $p->dias = $porDia > 0 ? max(0, $p->existencia) / $porDia : null;
                return $p;
            });
    }

    /** Desde esta existencia se considera que el número no es real (p. ej. 10.000 puesto a mano). */
    const EXISTENCIA_IRREAL = 1000;

    private function inventario()
    {
        $r = DB::table("productos")->where("existencia", ">", 0)->where("existencia", "<", self::EXISTENCIA_IRREAL)
            ->selectRaw("COALESCE(SUM(existencia * precio_compra), 0) AS costo, COALESCE(SUM(existencia * precio_venta), 0) AS venta")
            ->first();
        return (object) [
            "costo" => (float) $r->costo,
            "venta" => (float) $r->venta,
            "excluidos" => (int) DB::table("productos")->where("existencia", ">=", self::EXISTENCIA_IRREAL)->count(),
            "stockBajo" => (int) Producto::where("existencia", "<", Producto::UMBRAL_ALERTA)->count(),
        ];
    }

    /** Todo lo que muestra el tablero para un período. */
    public function datos($periodo)
    {
        if (!array_key_exists($periodo, self::PERIODOS)) {
            $periodo = "30d";
        }
        $r = $this->rango($periodo);
        $actual = $this->totales($r->desde, $r->hasta);
        // Hoy va a medio día: se compara con ayer hasta la misma hora
        $horaTope = $periodo === "hoy" ? date("H:i:s") : null;
        $anterior = $this->totales($r->antDesde, $r->antHasta, $horaTope);
        $ganancia = $this->ganancia($r->desde, $r->hasta);
        $gananciaAnt = $this->ganancia($r->antDesde, $r->antHasta, $horaTope);
        $promedio = $actual->numero > 0 ? $actual->total / $actual->numero : 0;
        $promedioAnt = $anterior->numero > 0 ? $anterior->total / $anterior->numero : 0;
        $fiados = app(FiadosService::class);

        return (object) [
            "periodo" => $periodo,
            "rango" => $r,
            "ventas" => (object) ["valor" => $actual->total, "cambio" => $this->cambio($actual->total, $anterior->total), "anterior" => $anterior->total],
            "numero" => (object) ["valor" => $actual->numero, "cambio" => $this->cambio($actual->numero, $anterior->numero)],
            "promedio" => (object) ["valor" => $promedio, "cambio" => $this->cambio($promedio, $promedioAnt)],
            "ganancia" => (object) ["valor" => $ganancia->ganancia, "margen" => $ganancia->margen, "cambio" => $this->cambio($ganancia->ganancia, $gananciaAnt->ganancia)],
            "fiadoPeriodo" => $actual->fiado,
            "porCobrar" => $fiados->totales()->por_cobrar,
            "deudores" => $fiados->deudores()->take(5),
            "serie" => $this->serie($r->desde, $r->hasta),
            "horas" => $this->horasPico($r->desde, $r->hasta),
            "topProductos" => $this->topProductos($r->desde, $r->hasta),
            "categorias" => $this->categorias($r->desde, $r->hasta),
            "porAgotarse" => $this->porAgotarse(),
            "inventario" => $this->inventario(),
        ];
    }
}
