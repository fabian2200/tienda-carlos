<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Equivale a la sección 2 del bloque del rediseño al final de database/tablas.sql (la base de la tienda se maneja con dumps).
 */
class CreateProveedoresTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable("proveedores")) {
            Schema::create("proveedores", function (Blueprint $table) {
                $table->id();
                $table->string("nombre", 150)->index();
                $table->string("nit", 30)->nullable();
                $table->string("telefono", 40)->nullable();
                $table->string("contacto", 120)->nullable();
                $table->string("direccion", 150)->nullable();
                $table->string("notas")->nullable();
                $table->timestamps();
            });
        }
        foreach (["productos", "compras"] as $tabla) {
            if (!Schema::hasColumn($tabla, "id_proveedor")) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->unsignedBigInteger("id_proveedor")->nullable()->index();
                });
            }
        }
    }

    public function down()
    {
        foreach (["productos", "compras"] as $tabla) {
            if (Schema::hasColumn($tabla, "id_proveedor")) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropColumn("id_proveedor");
                });
            }
        }
        Schema::dropIfExists("proveedores");
    }
}
