<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Equivale a la sección 4 del bloque del rediseño al final de database/estructura.sql (la base de la tienda se maneja con dumps).
 * Fiados y abonos pagados se marcan con pagado_en en vez de borrarse; fecha_abono pasa a DATETIME.
 */
class FiadosPagadosYFechaAbono extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn("fiados", "pagado_en")) {
            DB::statement("ALTER TABLE `fiados` ADD COLUMN `pagado_en` datetime NULL DEFAULT NULL, ADD KEY `fiados_id_cliente_index` (`id_cliente`), ADD KEY `fiados_id_factura_index` (`id_factura`)");
        }
        if (!Schema::hasColumn("abonos_fiados", "pagado_en")) {
            DB::statement("ALTER TABLE `abonos_fiados` ADD COLUMN `pagado_en` datetime NULL DEFAULT NULL, ADD KEY `abonos_fiados_id_cliente_index` (`id_cliente`)");
        }
        DB::statement("UPDATE `abonos_fiados` SET `fecha_abono` = DATE_FORMAT(STR_TO_DATE(`fecha_abono`, '%d-%m-%Y %H:%i:%s'), '%Y-%m-%d %H:%i:%s') WHERE `fecha_abono` REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}'");
        $tipo = DB::selectOne("SELECT DATA_TYPE AS tipo FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'abonos_fiados' AND COLUMN_NAME = 'fecha_abono'");
        if ($tipo && strtolower($tipo->tipo) !== "datetime") {
            DB::statement("ALTER TABLE `abonos_fiados` MODIFY `fecha_abono` datetime NULL DEFAULT NULL");
        }
    }

    public function down()
    {
        // No se revierte: volver a texto y borrar pagado_en perdería el historial.
    }
}
