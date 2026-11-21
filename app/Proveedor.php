<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = "proveedores";

    protected $fillable = ["nombre", "nit", "telefono", "contacto", "direccion", "notas"];

    public function productos()
    {
        return $this->hasMany("App\Producto", "id_proveedor");
    }

    /**
     * Busca un proveedor por nombre sin importar mayúsculas (para enlazar compras escritas a mano).
     */
    public static function porNombre($nombre)
    {
        $nombre = trim((string) $nombre);
        return $nombre === "" ? null : static::whereRaw("LOWER(nombre) = ?", [mb_strtolower($nombre)])->first();
    }
}
