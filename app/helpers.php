<?php

/*
 * Funciones globales del sistema. Se cargan desde composer.json ("autoload" → "files").
 */

if (!function_exists("dinero")) {
    /**
     * Formato de pesos colombianos: dinero(12500) → "$ 12.500".
     * Sin decimales por defecto (los precios de la tienda van en pesos enteros).
     */
    function dinero($valor, $decimales = 0)
    {
        $valor = (float) ($valor ?? 0);
        $signo = $valor < 0 ? "-" : "";
        return $signo . "$ " . number_format(abs($valor), $decimales, ",", ".");
    }
}

if (!function_exists("cantidad")) {
    /**
     * Cantidades: enteros sin decimales (3) y pesos con coma (0,75).
     */
    function cantidad($valor, $decimales = 2)
    {
        $valor = (float) ($valor ?? 0);
        if (floor($valor) == $valor) {
            return number_format($valor, 0, ",", ".");
        }
        return rtrim(rtrim(number_format($valor, $decimales, ",", "."), "0"), ",");
    }
}
