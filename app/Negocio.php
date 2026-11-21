<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Datos del negocio (una sola fila). Alimentan el encabezado y el pie de los
 * tickets, el PDF de inventario y el nombre que se ve en el sistema.
 */
class Negocio extends Model
{
    protected $table = "negocio";

    const CLAVE_CACHE = "negocio";

    // Se usan si la tabla todavía no existe (antes de aplicar el bloque del rediseño de database/estructura.sql).
    const VALORES_POR_DEFECTO = [
        "nombre" => "Provisiones Carlos Andres",
        "nit" => "12435619",
        "direccion" => "CRA 15 #13B Bis - 62",
        "barrio" => "Alfonso López",
        "ciudad" => null,
        "telefono" => null,
        "email" => null,
        "logo" => null,
        "regimen" => null,
        "resolucion_dian" => null,
        "mensaje_pie" => "Gracias por su compra",
        "firma_software" => "VentSOFT",
    ];

    protected $fillable = [
        "nombre", "nit", "direccion", "barrio", "ciudad", "telefono", "email",
        "logo", "regimen", "resolucion_dian", "mensaje_pie", "firma_software",
    ];

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget(self::CLAVE_CACHE);
        });
    }

    /**
     * Devuelve los datos del negocio, cacheados hasta que se editen.
     */
    public static function actual()
    {
        $negocio = Cache::get(self::CLAVE_CACHE);
        if ($negocio) {
            return $negocio;
        }

        try {
            $negocio = static::query()->orderBy("id")->first();
        } catch (QueryException $e) {
            $negocio = null; // la tabla aún no existe
        }

        if ($negocio) {
            Cache::forever(self::CLAVE_CACHE, $negocio);
            return $negocio;
        }

        return new static(self::VALORES_POR_DEFECTO);
    }

    /**
     * Iniciales para el logo cuando no hay imagen: "Provisiones Carlos Andres" → "CA".
     */
    public function iniciales()
    {
        $palabras = preg_split('/\s+/', trim((string) $this->nombre), -1, PREG_SPLIT_NO_EMPTY);
        $letras = array_map(function ($p) { return mb_substr($p, 0, 1); }, array_slice($palabras, -2));
        return mb_strtoupper(implode("", $letras)) ?: "PV";
    }

    /**
     * Barrio como se muestra: "Alfonso López" → "Brr. Alfonso López" (si no trae ya el prefijo).
     */
    public function barrioTexto()
    {
        $barrio = trim((string) $this->barrio);
        if ($barrio === "") {
            return null;
        }
        return preg_match('/^(brr|barrio)\b/i', $barrio) ? $barrio : "Brr. " . $barrio;
    }

    /**
     * URL pública del logo, o null si no hay.
     */
    public function urlLogo()
    {
        return $this->logo ? url($this->logo) : null;
    }

    /**
     * Ruta en disco del logo (para dompdf), o null si no hay.
     */
    public function rutaLogo()
    {
        if (!$this->logo) {
            return null;
        }
        $ruta = public_path($this->logo);
        return is_file($ruta) ? $ruta : null;
    }
}
