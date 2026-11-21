-- =====================================================================================
-- ESTRUCTURA de la base de la tienda (sin datos de negocio). Base: punto_venta_2.
--
-- Base nueva desde cero: ejecutar este archivo COMPLETO en una base vacía y luego database/datos.sql.
-- Base que ya tiene datos (PC de la tienda): ejecutar SOLO el bloque "REDISEÑO" del final.
--   Lo de arriba empieza cada tabla con DROP TABLE y borraría todo.
-- =====================================================================================

DROP TABLE IF EXISTS `abonos_fiados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abonos_fiados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `valor_abonado` double DEFAULT NULL,
  `fecha_abono` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proveedor` text,
  `total` text,
  `fecha` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=727 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS `fiados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `id_factura` int DEFAULT NULL,
  `total_fiado` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7867 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo_barras` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `precio_compra` decimal(9,2) NOT NULL,
  `precio_venta` decimal(9,2) NOT NULL,
  `existencia` decimal(9,2) NOT NULL,
  `unidad_medida` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `imagen` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `codigo_barras` (`codigo_barras`),
  KEY `idx_producto_id` (`codigo_barras`)
) ENGINE=InnoDB AUTO_INCREMENT=2063 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `total_pagar` double DEFAULT NULL,
  `total_dinero` double DEFAULT NULL,
  `total_fiado` double DEFAULT NULL,
  `total_vueltos` double DEFAULT NULL,
  `fecha_venta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `precio_domi` double DEFAULT '0',
  `id_usuario` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `ventas_id_cliente_foreign` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=65600 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS `productos_vendidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos_vendidos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_venta` bigint unsigned NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_barras` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio` decimal(9,2) NOT NULL,
  `cantidad` decimal(9,3) NOT NULL,
  `unidad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productos_vendidos_id_venta_foreign` (`id_venta`),
  CONSTRAINT `productos_vendidos_id_venta_foreign` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=168573 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- =====================================================================================
-- REDISEÑO (noviembre de 2026): datos del negocio, proveedores y productos de cada compra
-- =====================================================================================
-- Este bloque NO borra nada y se puede ejecutar más de una vez.
-- En la base de la tienda (que ya tiene datos) ejecuta SOLO desde esta línea hasta el final:
-- lo que está arriba en este archivo borra y vuelve a crear las tablas (DROP TABLE).

-- ---- 1. Datos del negocio ------------------------------------------------------------
-- Tabla con los datos del negocio (una sola fila).
-- Alimenta el encabezado y el pie de los tickets, el PDF de inventario y el nombre del sistema.
-- Aplicar una vez en la base de la tienda (es seguro repetirlo: no duplica la fila).

CREATE TABLE IF NOT EXISTS `negocio` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nit` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barrio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regimen` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolucion_dian` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensaje_pie` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firma_software` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `negocio` (`id`, `nombre`, `nit`, `direccion`, `barrio`, `mensaje_pie`, `firma_software`, `created_at`, `updated_at`)
SELECT 1, 'Provisiones Carlos Andres', '12435619', 'CRA 15 #13B Bis - 62', 'Alfonso López',
       'Gracias por su compra', 'VentSOFT', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `negocio`);

-- La firma ya no lleva nombre de persona (si la tabla se creó con la firma anterior)
UPDATE `negocio` SET `firma_software` = 'VentSOFT' WHERE `firma_software` LIKE 'VentSOFT By%';

-- ---- 2. Proveedores -------------------------------------------------------------------
-- Proveedores: tabla nueva y columna opcional id_proveedor en productos y compras.
-- Seguro de ejecutar más de una vez (revisa antes de crear cada cosa).
-- Las compras antiguas conservan el nombre del proveedor como texto (columna compras.proveedor).

CREATE TABLE IF NOT EXISTS `proveedores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nit` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proveedores_nombre_index` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- productos.id_proveedor
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'id_proveedor');
SET @sql := IF(@existe = 0,
    'ALTER TABLE `productos` ADD COLUMN `id_proveedor` bigint unsigned NULL DEFAULT NULL, ADD KEY `productos_id_proveedor_index` (`id_proveedor`)',
    'SELECT 1');
PREPARE consulta FROM @sql; EXECUTE consulta; DEALLOCATE PREPARE consulta;

-- compras.id_proveedor
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'compras' AND COLUMN_NAME = 'id_proveedor');
SET @sql := IF(@existe = 0,
    'ALTER TABLE `compras` ADD COLUMN `id_proveedor` bigint unsigned NULL DEFAULT NULL, ADD KEY `compras_id_proveedor_index` (`id_proveedor`)',
    'SELECT 1');
PREPARE consulta FROM @sql; EXECUTE consulta; DEALLOCATE PREPARE consulta;

-- ---- 3. Productos de cada compra -------------------------------------------------------
-- Productos de cada compra. Al registrar una compra con productos se suma la cantidad
-- a la existencia y se actualizan los precios de compra y venta del producto.
-- Se guardan también los precios anteriores para poder ver qué cambió.
-- Seguro de ejecutar más de una vez.

CREATE TABLE IF NOT EXISTS `compras_detalle` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_compra` int NOT NULL,
  `id_producto` bigint unsigned DEFAULT NULL,
  `codigo_barras` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad_medida` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `precio_compra` decimal(12,2) NOT NULL,
  `precio_venta` decimal(12,2) NOT NULL,
  `precio_compra_anterior` decimal(12,2) DEFAULT NULL,
  `precio_venta_anterior` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compras_detalle_id_compra_index` (`id_compra`),
  KEY `compras_detalle_id_producto_index` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- 4. Fiados pagados y fecha de los abonos -------------------------------------------
-- Cuando un cliente termina de pagar, sus fiados y abonos ya no se borran: se marcan con
-- la fecha en que quedó a paz y salvo (columna pagado_en). Así se conserva el historial.
-- La fecha de cada abono pasa de texto ("21-11-2026 10:00:00") a fecha real (DATETIME).
-- Seguro de ejecutar más de una vez.

-- fiados.pagado_en
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fiados' AND COLUMN_NAME = 'pagado_en');
SET @sql := IF(@existe = 0,
    'ALTER TABLE `fiados` ADD COLUMN `pagado_en` datetime NULL DEFAULT NULL, ADD KEY `fiados_id_cliente_index` (`id_cliente`), ADD KEY `fiados_id_factura_index` (`id_factura`)',
    'SELECT 1');
PREPARE consulta FROM @sql; EXECUTE consulta; DEALLOCATE PREPARE consulta;

-- abonos_fiados.pagado_en
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'abonos_fiados' AND COLUMN_NAME = 'pagado_en');
SET @sql := IF(@existe = 0,
    'ALTER TABLE `abonos_fiados` ADD COLUMN `pagado_en` datetime NULL DEFAULT NULL, ADD KEY `abonos_fiados_id_cliente_index` (`id_cliente`)',
    'SELECT 1');
PREPARE consulta FROM @sql; EXECUTE consulta; DEALLOCATE PREPARE consulta;

-- abonos_fiados.fecha_abono: de texto "dd-mm-aaaa hh:mm:ss" a DATETIME
UPDATE `abonos_fiados`
   SET `fecha_abono` = DATE_FORMAT(STR_TO_DATE(`fecha_abono`, '%d-%m-%Y %H:%i:%s'), '%Y-%m-%d %H:%i:%s')
 WHERE `fecha_abono` REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}';
SET @tipo := (SELECT DATA_TYPE FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'abonos_fiados' AND COLUMN_NAME = 'fecha_abono');
SET @sql := IF(@tipo <> 'datetime',
    'ALTER TABLE `abonos_fiados` MODIFY `fecha_abono` datetime NULL DEFAULT NULL',
    'SELECT 1');
PREPARE consulta FROM @sql; EXECUTE consulta; DEALLOCATE PREPARE consulta;
