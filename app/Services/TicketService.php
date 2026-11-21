<?php

namespace App\Services;

use App\Negocio;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

/**
 * Partes comunes de los tickets ESC/POS: impresora, encabezado y pie con los
 * datos de la tabla `negocio`. El detalle de cada ticket sigue en su controlador.
 */
class TicketService
{
    /**
     * Abre la impresora configurada en NOMBRE_IMPRESORA (.env de cada PC).
     */
    public static function impresora()
    {
        // Las pruebas pueden registrar 'ticket.conector' (p. ej. un archivo) para no imprimir de verdad
        if (app()->bound("ticket.conector")) {
            return new Printer(app("ticket.conector"));
        }
        $connector = new WindowsPrintConnector(env("NOMBRE_IMPRESORA"));
        return new Printer($connector);
    }

    /**
     * Líneas del encabezado del negocio, en el orden en que se imprimen.
     * También las usa la vista previa de "Datos del negocio".
     */
    public static function lineasEncabezado(Negocio $negocio = null)
    {
        $negocio = $negocio ?: Negocio::actual();
        $lineas = [$negocio->nombre];

        if ($negocio->nit) {
            $lineas[] = "NIT " . $negocio->nit;
        }
        if ($negocio->regimen) {
            $lineas[] = $negocio->regimen;
        }
        if ($negocio->direccion) {
            $lineas[] = $negocio->direccion;
        }
        if ($negocio->barrioTexto()) {
            $lineas[] = $negocio->barrioTexto();
        }
        if ($negocio->ciudad) {
            $lineas[] = $negocio->ciudad;
        }
        if ($negocio->telefono) {
            $lineas[] = "Tel. " . $negocio->telefono;
        }
        if ($negocio->resolucion_dian) {
            $lineas[] = $negocio->resolucion_dian;
        }

        return array_map([self::class, "texto"], $lineas);
    }

    /**
     * Título del ticket, datos del negocio y fecha, centrados y en negrilla.
     */
    public static function encabezado(Printer $impresora, $titulo, $fecha)
    {
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->setEmphasis(true);
        $impresora->text(self::texto($titulo) . "\n");
        foreach (self::lineasEncabezado() as $linea) {
            $impresora->text($linea . "\n");
        }
        $impresora->text($fecha . "\n");
        $impresora->setEmphasis(false);
    }

    /**
     * Mensaje de despedida (opcional) y firma del software.
     */
    public static function pie(Printer $impresora, $conMensaje = true)
    {
        $negocio = Negocio::actual();
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->setTextSize(1, 1);
        if ($conMensaje && $negocio->mensaje_pie) {
            $impresora->text(self::texto($negocio->mensaje_pie) . "\n");
        }
        if ($negocio->firma_software) {
            $impresora->text("\n" . self::texto($negocio->firma_software) . "\n");
        }
    }

    /**
     * La impresora térmica no imprime bien tildes ni eñes: se reemplazan por
     * su letra base (igual que estaban escritos los tickets antes).
     */
    public static function texto($texto)
    {
        return strtr((string) $texto, [
            "á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ü" => "u",
            "Á" => "A", "É" => "E", "Í" => "I", "Ó" => "O", "Ú" => "U", "Ü" => "U",
            "ñ" => "n", "Ñ" => "N",
        ]);
    }

    public static function redondearAl100($numero)
    {
        return round($numero / 100) * 100;
    }
}
