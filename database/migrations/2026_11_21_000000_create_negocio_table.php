<?php

use App\Negocio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Equivale a la sección 1 del bloque del rediseño al final de database/estructura.sql (la base de la tienda se maneja con dumps).
 */
class CreateNegocioTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable("negocio")) {
            Schema::create("negocio", function (Blueprint $table) {
                $table->id();
                $table->string("nombre", 150);
                $table->string("nit", 30);
                $table->string("direccion", 150);
                $table->string("barrio", 100)->nullable();
                $table->string("ciudad", 100)->nullable();
                $table->string("telefono", 30)->nullable();
                $table->string("email", 120)->nullable();
                $table->string("logo")->nullable();
                $table->string("regimen", 80)->nullable();
                $table->string("resolucion_dian")->nullable();
                $table->string("mensaje_pie")->nullable();
                $table->string("firma_software", 120)->nullable();
                $table->timestamps();
            });
        }

        if (DB::table("negocio")->count() === 0) {
            DB::table("negocio")->insert(array_merge(Negocio::VALORES_POR_DEFECTO, [
                "id" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ]));
        }
    }

    public function down()
    {
        Schema::dropIfExists("negocio");
    }
}
