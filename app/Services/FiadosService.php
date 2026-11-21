<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Única fuente de verdad para fiados y abonos. Todas las pantallas y tickets calculan
 * la deuda con estas funciones, así siempre dan lo mismo.
 *
 * Reglas:
 *  - Cuenta un fiado si está pendiente (pagado_en NULL) y su venta y su cliente existen.
 *  - Cuenta un abono si está pendiente (pagado_en NULL).
 *  - Deuda = fiados pendientes − abonos pendientes.
 *  - Cuando la deuda llega a cero, fiados y abonos se marcan con pagado_en (no se borran).
 */
class FiadosService
{
    /** Menos de esto se considera deuda saldada (centavos que deja el redondeo). */
    const TOLERANCIA = 1;

    /** Fiados pendientes con venta y cliente existentes. */
    public function fiadosPendientes()
    {
        return DB::table("fiados")
            ->join("clientes", "clientes.id", "=", "fiados.id_cliente")
            ->join("ventas", "ventas.id", "=", "fiados.id_factura")
            ->whereNull("fiados.pagado_en");
    }

    public function abonosPendientes()
    {
        return DB::table("abonos_fiados")->whereNull("abonos_fiados.pagado_en");
    }

    /**
     * Fiado, abonado y deuda de un cliente.
     * @return object {fiado, abonado, deuda}
     */
    public function estadoCliente($idCliente)
    {
        $fiado = (float) $this->fiadosPendientes()->where("fiados.id_cliente", $idCliente)->sum("fiados.total_fiado");
        $abonado = (float) $this->abonosPendientes()->where("abonos_fiados.id_cliente", $idCliente)->sum("abonos_fiados.valor_abonado");
        return (object) ["fiado" => $fiado, "abonado" => $abonado, "deuda" => $fiado - $abonado];
    }

    /**
     * Clientes con fiados pendientes, con su fiado, abonado y deuda (en una sola consulta).
     */
    public function clientesConFiado()
    {
        $abonos = $this->abonosPendientes()
            ->select("id_cliente", DB::raw("SUM(valor_abonado) as total_abonado"))
            ->groupBy("id_cliente");

        return $this->fiadosPendientes()
            ->leftJoinSub($abonos, "abonos", "abonos.id_cliente", "=", "clientes.id")
            ->select("clientes.*")
            ->selectRaw("SUM(fiados.total_fiado) as total_fiado")
            ->selectRaw("COALESCE(MAX(abonos.total_abonado), 0) as total_abonado")
            ->selectRaw("SUM(fiados.total_fiado) - COALESCE(MAX(abonos.total_abonado), 0) as total_deuda")
            ->groupBy("clientes.id")
            ->get();
    }

    /** Clientes que deben (deuda mayor que la tolerancia), los que más deben primero. */
    public function deudores()
    {
        return $this->clientesConFiado()
            ->filter(function ($c) { return $c->total_deuda >= self::TOLERANCIA; })
            ->sortByDesc("total_deuda")
            ->values();
    }

    /** Totales de la pantalla Fiados: fiado, abonado y por cobrar de los clientes con fiados pendientes. */
    public function totales()
    {
        $clientes = $this->clientesConFiado();
        $fiado = (float) $clientes->sum("total_fiado");
        $abonado = (float) $clientes->sum("total_abonado");
        return (object) ["fiado" => $fiado, "abonado" => $abonado, "por_cobrar" => $fiado - $abonado];
    }

    /** Ventas con fiado pendiente de un cliente, con lo que quedó fiado en cada una. */
    public function facturasPendientes($idCliente)
    {
        return $this->fiadosPendientes()
            ->where("fiados.id_cliente", $idCliente)
            ->select("ventas.*", "fiados.total_fiado")
            ->orderBy("ventas.id")
            ->get();
    }

    /**
     * Registra un abono. Valida el valor contra la deuda real y, si queda saldada,
     * marca fiados y abonos como pagados. Todo en una sola transacción.
     *
     * @return object {abonado, deuda_restante, saldada}
     * @throws InvalidArgumentException si el valor no es válido
     */
    public function registrarAbono($idCliente, $valor)
    {
        if (!is_numeric($valor) || $valor <= 0) {
            throw new InvalidArgumentException("El valor del abono debe ser mayor que cero.");
        }
        $valor = (float) $valor;

        return DB::transaction(function () use ($idCliente, $valor) {
            // Bloquea al cliente: dos abonos al mismo tiempo no se cruzan
            $cliente = DB::table("clientes")->where("id", $idCliente)->lockForUpdate()->first();
            if (!$cliente) {
                throw new InvalidArgumentException("El cliente no existe.");
            }

            $estado = $this->estadoCliente($idCliente);
            if ($estado->deuda < self::TOLERANCIA) {
                throw new InvalidArgumentException("Este cliente no tiene deuda pendiente.");
            }
            if ($valor > round($estado->deuda)) {
                throw new InvalidArgumentException("El abono es mayor que la deuda. Debe " . dinero($estado->deuda) . ".");
            }

            DB::table("abonos_fiados")->insert([
                "id_cliente" => $idCliente,
                "valor_abonado" => $valor,
                "fecha_abono" => now(),
            ]);

            $restante = $estado->deuda - $valor;
            $saldada = $restante < self::TOLERANCIA;
            if ($saldada) {
                $this->marcarPagado($idCliente);
            }

            return (object) ["abonado" => $valor, "deuda_restante" => max(0, $restante), "saldada" => $saldada];
        });
    }

    /** Deja al cliente a paz y salvo: marca sus fiados y abonos pendientes como pagados. */
    public function marcarPagado($idCliente)
    {
        $ahora = now();
        DB::table("fiados")->where("id_cliente", $idCliente)->whereNull("pagado_en")->update(["pagado_en" => $ahora]);
        DB::table("abonos_fiados")->where("id_cliente", $idCliente)->whereNull("pagado_en")->update(["pagado_en" => $ahora]);
    }

    /** Guarda lo que quedó fiado en una venta (llamar dentro de la transacción de la venta). */
    public function guardarFiado($idCliente, $idVenta, $valor)
    {
        if ($valor > 0) {
            DB::table("fiados")->insert([
                "id_cliente" => $idCliente,
                "id_factura" => $idVenta,
                "total_fiado" => $valor,
            ]);
        }
    }

    /**
     * Quita el fiado de una venta que se elimina (llamar dentro de la transacción del borrado).
     * Si con eso los abonos del cliente cubren lo que queda, se deja a paz y salvo.
     *
     * @return float lo que el cliente había abonado de más (0 si nada)
     */
    public function quitarFiadoDeVenta($idVenta)
    {
        $clientes = DB::table("fiados")->where("id_factura", $idVenta)->pluck("id_cliente")->unique();
        DB::table("fiados")->where("id_factura", $idVenta)->delete();

        $deMas = 0;
        foreach ($clientes as $idCliente) {
            $estado = $this->estadoCliente($idCliente);
            if ($estado->abonado > 0 && $estado->deuda < self::TOLERANCIA) {
                $deMas += max(0, -$estado->deuda);
                $this->marcarPagado($idCliente);
            }
        }
        return $deMas;
    }
}
