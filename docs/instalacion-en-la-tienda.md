# Instalar la interfaz nueva en el PC de la tienda

Guía para pasar los cambios del rediseño (fases 1 a 11 de `docs/plan-mejora-interfaz.md`) al computador de la caja. Toma unos 20 minutos. Hazlo con la tienda cerrada o sin ventas en curso.

## Antes de empezar

- El sistema corre con **PHP 7.4** (`C:\xampp\php\php.exe`). Con PHP 8.2 Laravel 7 no arranca. En los comandos de abajo `php` significa ese PHP 7.4.
- La base de datos es **MySQL 8**. El cliente `mysql.exe` de XAMPP no se conecta a ella (error `caching_sha2_password`); usa MySQL Workbench o el `mysql.exe`/`mysqldump.exe` que trae MySQL 8.

## 1. Respaldo de la base de datos

Con MySQL Workbench: **Server → Data Export**, elige la base del sistema, "Export to Self-Contained File", y guarda el archivo con la fecha (por ejemplo `respaldo-2026-11-21.sql`) fuera de la carpeta del proyecto.

O desde la consola, con el `mysqldump` de MySQL 8:

```powershell
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe" -u root -p --routines NOMBRE_BASE > C:\respaldos\respaldo-2026-11-21.sql
```

No sigas si el respaldo no se creó o pesa 0 KB.

## 2. Copiar el código

En la carpeta del sistema (la que tiene `artisan`):

```powershell
git pull
```

Si la tienda no usa git, copia las carpetas `app`, `config`, `database`, `docs`, `public`, `resources`, `routes` y el archivo `composer.json`, **sin** tocar `.env`, `vendor` ni `storage`.

## 3. Registrar las funciones nuevas

El rediseño agregó `app/helpers.php` (formato de pesos). Composer debe conocerlo:

```powershell
composer dump-autoload --no-scripts
```

El `--no-scripts` evita que Composer intente correr `artisan` con PHP 8.2 y falle.

## 4. Crear las tablas nuevas

El SQL está al final de `database/tablas.sql`, en el bloque que empieza con **`-- REDISEÑO (noviembre de 2026)`**.

> **No ejecutes `tablas.sql` completo en la base de la tienda.** La parte de arriba de ese archivo empieza cada tabla con `DROP TABLE` y borraría todas las ventas, productos y clientes. Ejecuta **solo el bloque del rediseño**: desde la línea `-- ====...` que está justo encima de `-- REDISEÑO` hasta el final del archivo.

En MySQL Workbench: abre `tablas.sql`, selecciona desde esa línea hasta el final, cópialo en una pestaña nueva de consulta sobre la base del sistema y ejecútalo. El bloque no borra nada y se puede ejecutar más de una vez. Hace tres cosas:

1. Crea la tabla `negocio` con los datos actuales (Provisiones Carlos Andres, NIT 12435619…) y deja la firma del software como "VentSOFT".
2. Crea la tabla `proveedores` y agrega la columna opcional `id_proveedor` a `productos` y `compras`. Las compras antiguas conservan el nombre del proveedor escrito a mano.
3. Crea la tabla `compras_detalle` con los productos de cada compra.

(Para armar una base nueva desde cero sí se ejecuta `tablas.sql` completo: el bloque del rediseño ya va incluido al final.)

## 5. Limpiar cachés

```powershell
C:\xampp\php\php.exe artisan view:clear
```

```powershell
C:\xampp\php\php.exe artisan cache:clear
```

## 6. Revisar el `.env`

- `NOMBRE_IMPRESORA` sigue siendo el nombre de la impresora compartida (`pos-88`).
- Recomendado: `APP_DEBUG=false`. Así, si algo falla, se ve la página "Algo salió mal" en español en lugar de la pantalla técnica de Laravel.

La carpeta `public/img/negocio` debe poder escribirse (ahí se guarda el logo).

## 7. Prueba en la caja

Entra como **admin** y revisa, en este orden:

| # | Qué hacer | Qué debe pasar |
|---|---|---|
| 1 | Abrir el sistema | Login nuevo con los datos del negocio a la izquierda |
| 2 | **Datos del negocio** | Aparecen el NIT y la dirección actuales; la vista previa del ticket se actualiza al escribir |
| 3 | **Vender**: escanear 2 productos por unidad | Aparecen arriba en el carrito; el total se actualiza |
| 4 | Escanear un producto por kilo y presionar **F8** | Se abre "Agregar producto" y la balanza llena el peso |
| 5 | Escribir lo recibido y **F9** con "Imprimir ticket" activo | Sale el ticket con el encabezado de la tabla `negocio`; el carrito queda vacío |
| 6 | **Ventas** → la venta recién hecha → **Reimprimir ticket** | Sale la copia del ticket |
| 7 | **Descargar PDF** de esa venta | PDF con los datos del negocio |
| 8 | **Fiados** → un cliente → **Imprimir estado de cuenta** | Sale el ticket de deuda |
| 9 | **Proveedores** → Agregar proveedor; luego **Productos** → editar uno y elegir ese proveedor | El producto aparece al filtrar el listado por ese proveedor |
| 10 | **Compras** → Registrar compra: elegir proveedor, escanear un producto, poner la cantidad y cambiar el precio de venta | Al guardar, la existencia del producto sube en esa cantidad y quedan los precios nuevos (revísalo en **Productos**) |
| 11 | Eliminar esa compra de prueba | La existencia vuelve a como estaba; los precios se quedan con los nuevos (corrígelos con "Inventario y precio" si hace falta) |
| 12 | Entrar con una caja (por ejemplo `caja2`) | El menú no muestra Administración; Vender funciona igual |

Si la venta de prueba no se necesita, elimínala desde **Ventas** (las existencias no se devuelven solas: súmalas con "Inventario y precio").

## Si algo sale mal

1. Restaura el respaldo del paso 1 (Workbench: **Server → Data Import**).
2. Vuelve al código anterior: `git checkout <commit anterior>` (o recupera la copia de las carpetas).
3. `C:\xampp\php\php.exe artisan view:clear`

Las tablas `negocio`, `proveedores` y `compras_detalle` y la columna `id_proveedor` pueden quedarse aunque vuelvas al código anterior: el sistema viejo no las usa.
