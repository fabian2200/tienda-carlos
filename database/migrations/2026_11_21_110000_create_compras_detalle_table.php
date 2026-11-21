<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Equivale a la sección 3 del bloque del rediseño al final de database/tablas.sql (la base de la tienda se maneja con dumps).
 */
class CreateComprasDetalleTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable("compras_detalle")) {
            return;
        }
        Schema::create("compras_detalle", function (Blueprint $table) {
            $table->id();
            $table->integer("id_compra")->index();
            $table->unsignedBigInteger("id_producto")->nullable()->index();
            $table->string("codigo_barras");
            $table->string("descripcion");
            $table->string("unidad_medida", 20);
            $table->decimal("cantidad", 12, 3);
            $table->decimal("precio_compra", 12, 2);
            $table->decimal("precio_venta", 12, 2);
            $table->decimal("precio_compra_anterior", 12, 2)->nullable();
            $table->decimal("precio_venta_anterior", 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists("compras_detalle");
    }
}
