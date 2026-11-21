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

if (!function_exists("dinero_corto")) {
    /**
     * Pesos abreviados para gráficas y cifras grandes:
     * 850 → "$ 850", 45000 → "$ 45 mil", 1250000 → "$ 1,3 M", 12000000 → "$ 12 M".
     */
    function dinero_corto($valor)
    {
        $valor = (float) ($valor ?? 0);
        $signo = $valor < 0 ? "-" : "";
        $v = abs($valor);
        if ($v >= 999500) {
            $m = $v / 1000000;
            $texto = number_format($m, $m < 10 && round($m, 1) != round($m) ? 1 : 0, ",", ".") . " M";
        } elseif ($v >= 1000) {
            $texto = number_format($v / 1000, 0, ",", ".") . " mil";
        } else {
            $texto = number_format($v, 0, ",", ".");
        }
        return $signo . "$ " . $texto;
    }
}
