# Plan: rediseño de la interfaz + datos del negocio desde la BD

## Estado (21 de noviembre de 2026)

| Fase | Estado |
|---|---|
| 1–2 Tabla `negocio` y tickets | Hecha. El SQL está al final de `database/estructura.sql` (bloque del rediseño) y ya se aplicó en la base local |
| 3 Sistema visual (`public/css/pos.css`, fuentes y librerías locales) | Hecha |
| 4 Menú lateral y barra superior | Hecha |
| 5 Login | Hecha |
| 6 Inicio | Hecha |
| 7 Productos | Hecha |
| 8 Vender | Hecha |
| 9 Ventas y factura PDF | Hecha |
| 10 Resto de pantallas | Hecha (Domicilios sigue oculta y sin rediseñar) |
| 11 Verificación | Hecha en PC local. Falta la prueba con la impresora `pos-88` y la balanza reales: ver `docs/instalacion-en-la-tienda.md` |

Pendientes fuera de este plan (ya existían): `/register` abierta sin sesión; Cajas y Usuarios sin restricción de admin en el servidor; vistas sin uso `ventas_mes`, `productos_alert` y `domicilios`; zona horaria `America/Mexico_City` (una hora menos que Colombia).

Proyecto: `D:\tienda-carlos` — Laravel 7, PHP 7.2, Blade, Bootstrap 4.4, jQuery, DataTables, SweetAlert, bootstrap-select, FontAwesome, impresión ESC/POS (`mike42/escpos-php`).

## Situación actual (lo que encontré)

**Datos del negocio quemados en el código**, repetidos en 5 lugares:

| Archivo | Qué imprime |
|---|---|
| `app/Http/Controllers/VentasController.php` → `ticket()` | encabezado del ticket de venta |
| `app/Http/Controllers/VentasController.php` → `ImprimirTicket()` | reimpresión (copia casi idéntica del anterior) |
| `app/Http/Controllers/DomiciliosController.php` → `ticket()` | ticket de domicilio |
| `app/Http/Controllers/UserController.php` → `ImprimirDeuda()` | ticket de deuda |
| `resources/views/pdf_productos.blade.php` | título del PDF de productos |

Todos repiten "Provisiones Carlos Andres / NIT 12435619 / CRA 15 #13B Bis - 62 / Brr. Alfonso Lopez", "Gracias por su compra" y la firma del software. El título de la pestaña sale de `env("APP_NAME")` y el pie del layout es texto fijo.

**Interfaz:**
- `maestra.blade.php`: barra superior con ~10 botones de colores distintos (info, success, warning, danger, dark), CSS en línea dentro del layout, pie fijo que tapa contenido.
- Login: fondo `frutas.png` estirado (`background-size: 100% 134%`), tarjeta Bootstrap genérica que dice "Login".
- Home: tarjetas-botón de 5 colores distintos con estilos en línea.
- Productos: tabla con botones dentro de cada celda separados por `<hr>` (Editar código, Cambiar porcentaje, Agregar inventario, Editar, Eliminar) → filas muy altas, difícil de leer. Precios sin formato de moneda.
- Vender: totales en `<h2>` con fondo aqua/amarillo, modal de cobro con 4 campos sueltos, "Imprimir factura" como select si/no.
- Ventas: tarjetas de totales con colores fijos, tabla con 7 columnas de dinero, eliminar sin confirmación visible.
- Estilos en línea en casi todas las vistas (`style="font-size: 20px"` repetido decenas de veces).

**Rendimiento a corregir de paso en las pantallas que se toquen:**
- `VentasController@index` trae **todas** las ventas del usuario para sumar el mes en PHP; luego hace un query por cliente para los abonos (N+1).
- El admin se detecta con `Auth::user()->email == 'admin'` (se deja igual; ver decisiones).

---

## Fase 1 — Tabla `negocio` y servicio de datos del negocio

1. **Migración** `database/migrations/2026_11_21_000000_create_negocio_table.php` (una sola fila):

   | Campo | Tipo | Ejemplo |
   |---|---|---|
   | `id` | bigint PK | 1 |
   | `nombre` | varchar(150) | Provisiones Carlos Andres |
   | `nit` | varchar(30) | 12435619 |
   | `direccion` | varchar(150) | CRA 15 #13B Bis - 62 |
   | `barrio` | varchar(100) null | Brr. Alfonso Lopez |
   | `ciudad` | varchar(100) null | |
   | `telefono` | varchar(30) null | |
   | `email` | varchar(120) null | |
   | `logo` | varchar(255) null | ruta en `public/img/negocio/` |
   | `regimen` | varchar(80) null | No responsable de IVA |
   | `resolucion_dian` | varchar(255) null | (si algún día factura electrónica) |
   | `mensaje_pie` | varchar(255) | Gracias por su compra |
   | `firma_software` | varchar(120) null | VentSOFT |
   | `timestamps` | | |

   Como la BD real se maneja con dumps (`database/estructura.sql` y `database/datos.sql`), además de la migración dejar `database/negocio.sql` con `CREATE TABLE` + `INSERT` de los datos actuales, para aplicarlo a mano en el PC de la tienda.

2. **Modelo** `app/Negocio.php` (los modelos están en `app/`, no en `app/Models`), `protected $table = 'negocio'`, con `Negocio::actual()` que devuelve la fila 1 cacheada (`Cache::rememberForever('negocio', ...)`) y limpia la caché al guardar.

3. **Compartir en vistas**: en `AppServiceProvider::boot()` un `View::composer('*', ...)` que comparta `$negocio`. Así el layout, el login, el título de la pestaña y el PDF lo usan sin tocar cada controlador.

4. **Pantalla de configuración** (solo admin): `NegocioController@edit/update`, ruta `negocio.edit` / `negocio.update`, vista `resources/views/negocio/negocio_edit.blade.php` con formulario + subida de logo + **vista previa del ticket** en vivo (un `<pre>` de 32 columnas que simula la tirilla de 58/80 mm). Acceso desde el menú "Configuración".

## Fase 2 — Unificar los tickets en un servicio

1. Crear `app/Services/TicketService.php` con:
   - `encabezado(Printer $p)` → lee `Negocio::actual()` e imprime nombre, NIT, dirección, barrio, teléfono (omite los vacíos).
   - `pie(Printer $p)` → `mensaje_pie` y `firma_software`.
   - `imprimirVenta(Venta $v, bool $conDomicilio = false, ?string $direccion = null)`.
   - `imprimirDeuda($cliente, $ventas, $abonado)`.
   - `redondearAl100()` en un solo sitio (hoy está copiado en 3 controladores).
2. Reemplazar `VentasController::ticket`, `VentasController::ImprimirTicket`, `DomiciliosController::ticket` y `UserController::ImprimirDeuda` por llamadas al servicio. **Conservar rutas y nombres de métodos públicos** (`/imprimir-ticket`, `/imprimir-deuda`, `VenderController` llama a `$objeto->ticket(...)`).
3. `pdf_productos.blade.php`: usar `$negocio->nombre` y el logo.
4. Prueba: imprimir una venta normal, una con domicilio, una reimpresión y una deuda en la `pos-88`, comparando con un ticket viejo.

## Dirección visual: moderna y elegante, con identidad de tienda de barrio

Idea central: un punto de venta sobrio y calmado, con colores tomados de lo que se vende en una tienda de provisiones (hoja de plátano y maíz) en vez del azul genérico de Bootstrap. Todo es silencioso salvo **un** elemento memorable: la **tirilla en vivo** de la pantalla Vender (ver Fase 8).

**Paleta** (variables en `:root` de `pos.css`):

| Token | Hex | Uso |
|---|---|---|
| `--hoja` | `#1E5B45` | color principal: menú lateral, botones primarios, enlaces |
| `--hoja-oscura` | `#143F30` | fondo del menú lateral, encabezado del login |
| `--mazorca` | `#E2A93B` | acento único: botón **Cobrar**, total de la venta, ítem activo del menú |
| `--lienzo` | `#F4F6F3` | fondo de la aplicación (gris verdoso frío, no crema) |
| `--tinta` | `#1D2622` | texto principal |
| `--niebla` | `#DCE2DE` | bordes, separadores de tabla |
| `--alerta` | `#B3343F` | eliminar, cancelar, stock agotado |

Regla: el amarillo `--mazorca` aparece una sola vez por pantalla (la acción principal). El resto es verde, blanco y grises.

**Tipografía** (archivos `.woff2` en `public/fonts/`, **sin Google Fonts**, para que funcione sin internet):
- **Bricolage Grotesque** (600–700): títulos de página, nombre del negocio, el total de la venta y **todas las cifras** (precios, cantidades, códigos). En Atkinson el 0 lleva una raya y los precios parecen tachados.
- **Atkinson Hyperlegible** (400/700): todo lo demás. Fue diseñada para lectura rápida; distingue bien 1/l/I y 0/O, clave en códigos y precios.
- Escala: 14 · 16 (base) · 20 · 28 · 40 · 64 (total en Vender). Cifras con `font-variant-numeric: tabular-nums` y alineadas a la derecha.
- Títulos en tipo oración, nada en mayúsculas sostenidas, sin etiquetas pequeñas encima de los títulos.

**Forma y profundidad:**
- Radios con jerarquía: 6 px en campos y botones, 12 px en paneles, 0 px en la tirilla (borde dentado de papel).
- Sin tarjetas repetidas con la misma sombra: las secciones se separan con espacio y una línea `--niebla`; solo los modales y la tirilla tienen sombra.
- Tablas sin rejilla completa: líneas horizontales finas, fila al pasar el mouse en `--lienzo`, acciones como iconos que se ven al pasar sobre la fila.
- Foco de teclado visible (anillo `--mazorca` de 3 px): la caja se usa mucho con teclado y lector.

**Movimiento:** casi nada. Solo cuando responde a una acción: el producto recién escaneado aparece en la tirilla con un breve deslizamiento, y el cambio a devolver se resalta al calcularse. Se respeta `prefers-reduced-motion`.

**Textos:** verbos claros y el mismo nombre en todo el flujo ("Cobrar" → "Venta cobrada"; "Eliminar venta" → "Venta eliminada"). Errores que dicen qué pasó y qué hacer ("No hay existencias de Arroz Diana 500 g. Agrega inventario o quítalo de la venta."). Pantallas vacías con la acción para llenarlas ("Aún no hay ventas hoy. Ir a Vender.").

```
Layout general (≥1200 px)                 Vender
┌────────┬──────────────────────────┐    ┌──────────────────────────┬───────────────┐
│ LOGO   │ Productos         [+ Reg]│    │ [ Escanea o busca…  F2 ] │ ┌───────────┐ │
│ Negocio│──────────────────────────│    │──────────────────────────│ │ PROVISIONES│ │
│        │ [buscar…]  [categoría ▾] │    │ Arroz Diana 500g   1  $… │ │ NIT …      │ │
│ Vender │──────────────────────────│    │ Leche Alquería     2  $… │ │ ……………… $… │ │
│ Ventas │ código  producto   $ stock│    │ Tomate (kg)    0,75   $… │ │ ……………… $… │ │
│ Produc.│ ───────────────────────── │    │                          │ │ Total      │ │
│ Client.│ ───────────────────────── │    │                          │ │ $ 48.300   │ │
│ …      │                          │    │                          │ └╲╱╲╱╲╱╲╱╲╱┘ │
│ Config │                          │    │                          │ [  Cobrar F9 ]│
└────────┴──────────────────────────┘    └──────────────────────────┴───────────────┘
```
Contenido alineado a la izquierda; cifras a la derecha.

## Fase 3 — Sistema visual base (antes de rediseñar pantallas)

Decisión técnica: **quedarse en Bootstrap 4.4.1** (el tema Bootswatch Lumen actual; no se sube a 4.6 para no tocar bootstrap-select 1.10). bootstrap-select y los modales actuales dependen de BS4/jQuery; pasar a BS5 obliga a reescribir todos los `data-toggle` y cambiar el select → mucho riesgo para poco valor. Bootstrap queda como base de rejilla y componentes; la apariencia la da `pos.css`.

1. `public/css/pos.css` nuevo con los tokens de la dirección visual (paleta, tipografía, radios), sobrescribiendo los colores de Bootstrap (`.btn-primary` → `--hoja`, etc.). Fuentes en `public/fonts/` con `@font-face`.
2. Mover a `pos.css` todo el `<style>` de `maestra.blade.php` y los `style="..."` repetidos (clases: `.titulo-pagina`, `.tarjeta-kpi`, `.input-grande`, `.btn-accion`, `.tabla-pos`).
3. Descargar a `public/` lo que hoy viene de CDN (popper, bootstrap.js, DataTables Buttons, pdfmake, jszip) → el sistema funciona aunque falle internet en la tienda.
4. Helper Blade/PHP `dinero($valor)` → `$ 12.500` (formato colombiano, sin decimales) para usar en todas las vistas.

## Fase 4 — Layout general (`maestra.blade.php`)

1. **Barra lateral** fija (colapsable en pantallas pequeñas) con: logo + nombre del negocio (de `$negocio`), y secciones:
   - **Vender** (botón destacado arriba)
   - Operación: Ventas, Ventas por fecha, Compras, Domicilios (si se reactiva)
   - Inventario: Productos, Productos en alerta (con contador)
   - Personas: Clientes, Deudores, Usuarios
   - Admin: Cajas, Registrar usuario, **Configuración del negocio**
2. Barra superior delgada: título de la página, usuario conectado y botón Salir.
3. Ítem activo resaltado según `request()->routeIs(...)`.
4. `<title>`: `@yield("titulo") - {{ $negocio->nombre }}`. Quitar el pie fijo (o dejarlo pequeño, no `fixed-bottom`).
5. Mensajes (`notificacion.blade.php`) como toasts de SweetAlert en vez de alertas que empujan el contenido.

## Fase 5 — Login

1. Vista propia sin la barra de navegación (`layouts/auth` o `@section` que oculte el menú).
2. Pantalla dividida: a la izquierda logo, nombre, NIT y dirección del negocio (desde `negocio`); a la derecha el formulario (Usuario, Contraseña con botón de mostrar, "Iniciar sesión").
3. Fondo con `background-size: cover` (o un degradado del color de marca) en lugar de la imagen estirada.
4. Errores en español y visibles; foco automático en usuario; Enter envía.

## Fase 6 — Inicio (home)

1. Reemplazar las tarjetas de colores por un **tablero**: KPIs del día (vendido hoy, # ventas hoy, fiado pendiente, productos en alerta).
2. Accesos rápidos con icono uniforme (mismo estilo, un solo color de acento), "Vender" como acción principal grande.
3. Las consultas de KPIs en un `app/Services/ResumenVentasService.php` usando `SUM()` en SQL (reutilizable por Ventas).

## Fase 7 — Listado de productos

1. Tabla limpia de una línea por producto: Código · Descripción · Compra · Venta · Utilidad (valor y %) · Existencia (con **badge** rojo/amarillo/verde según stock vs mínimo) · Acciones.
2. Acciones en un **menú desplegable** (⋯) o botones de icono pequeños con tooltip: Editar, Agregar inventario, Cambiar %, Editar código, Eliminar (con confirmación SweetAlert).
3. Buscador arriba a la izquierda con búsqueda al escribir (debounce) manteniendo la paginación del servidor actual (`?search=`); filtros por categoría y "solo con stock bajo".
4. Botones "Registrar producto" y "Exportar PDF" juntos en el encabezado de la página.
5. Precios con `dinero()`. Formularios crear/editar en 2 columnas con imagen del producto a un lado.

## Fase 8 — Ventana de Vender (la más importante)

Pensada para caja con lector de código de barras y balanza:

**Requisito: todo el proceso de venta y cobro en una sola ventana, sin scroll de página ni ventana emergente de cobro.** Probado en la maqueta (`docs/maqueta/maqueta-interfaz.html`) a 1366×768 y 1280×650.

1. **Pantalla fija al alto de la ventana** (`height: 100vh`, `overflow: hidden` en la página). En Vender el menú lateral se contrae a iconos (72 px, nombre al pasar el mouse) para ganar ancho; barra superior delgada de 52 px.
2. **Dos columnas**: izquierda lector + carrito; derecha (390 px) la **tirilla de cobro**.
3. Campo de código grande, siempre con foco (volver a enfocarlo tras cada acción), con botones Buscar (F2) y Balanza (F4) al lado.
4. Carrito: el **producto más reciente va arriba**, así lo último escaneado siempre se ve. Filas compactas (~48 px, caben ~8–10 productos); si hay más, solo la lista se desplaza por dentro y el encabezado de la tabla queda fijo. Cantidad con − / +, subtotal, quitar con icono (visible al pasar el mouse). Pie con atajos y conteo de productos/unidades.
5. **Tirilla = panel de cobro** (sin modal): encabezado del negocio desde la tabla `negocio`, total en Bricolage Grotesque grande, cliente, campo **Recibido** + caja de **Cambio** al lado (se vuelve roja "Falta" si no alcanza, o ámbar "Queda fiado" si está activo fiar), botones rápidos Exacto / 20.000 / 50.000 / 100.000 / 200.000, chips **Fiar lo que falta** e **Imprimir ticket** (recordar la última elección en `localStorage`), botón **Cobrar (F9)** en `--mazorca` anclado abajo y "Cancelar venta" discreto.
6. Mensajes de resultado como aviso breve arriba ("Venta cobrada · imprimiendo ticket"), sin bloquear la pantalla.
7. Producto por peso y búsqueda manual: una ventana pequeña "Agregar producto" (cantidad ⇄ valor, "Leer balanza"). Es la única ventana emergente; el cobro no la usa.
8. Atajos: F2 buscar, F4 ir a Recibido, F8 balanza (la misma tecla de antes), F9 cobrar, Supr quita el último producto. Media query para pantallas bajas (`max-height: 700px`) que reduce rellenos y el tamaño del total.
9. Reglas de cobro: Recibido vacío = pago exacto; si falta dinero hay que activar "Fiar lo que falta"; para fiar hay que elegir un cliente distinto del primero (PUBLICO EN GENERAL). Se guardan los mismos campos que antes (vueltos = recibido − total, negativo cuando hay fiado).
10. **No cambiar** los endpoints AJAX existentes (`agregarProductoVenta`, `quitarProductoDeVenta`, `actualizarProductoDeVenta`, `terminarOCancelarVenta`, `productos-carrito`, `leer-peso`) — solo la presentación y el JS de la vista. Mover el JS inline (~500 líneas) a `public/js/vender.js`.

## Fase 9 — Ventas (listado, detalle y por fecha)

1. KPIs arriba con el mismo componente `.tarjeta-kpi` (Hoy, Mes, Fiado pendiente) y colores del sistema.
2. Tabla: Fecha (formato `d/m/Y h:i a`) · # Factura · Cliente · Total · Forma (Contado / Fiado / Domicilio como badge) · Acciones (Ver, Reimprimir, Eliminar con confirmación). Pago/vueltos/domicilio pasan al detalle para no tener 7 columnas de dinero.
3. Filtro por rango de fechas y cliente en la misma pantalla (unifica "Ventas" y "Ventas por mes").
4. **Detalle de venta** (`ventas_show`) con aspecto de factura: encabezado desde `negocio` (logo, nombre, NIT, dirección), datos de la venta, tabla de productos, totales, botón "Reimprimir ticket" y "Descargar PDF" (dompdf ya está instalado).
5. Backend: sumar el mes con `SUM()` en SQL y calcular el fiado pendiente con un solo query (`fiados` − `abonos_fiados` agrupado), quitando el N+1.

## Fase 10 — Resto de pantallas

Aplicar el mismo sistema (encabezado de página, tabla `.tabla-pos`, acciones con icono, confirmaciones SweetAlert) a: Clientes, Usuarios, Clientes deudores + info de deuda, Compras, Cajas, Productos en alerta, Domicilios, Acerca de. Son cambios de presentación, sin tocar lógica.

## Fase 11 — Verificación

- Recorrer cada pantalla en el navegador (PC de caja ~1366×768 y una tableta) sin errores en consola.
- Venta completa: lector → balanza → cobrar → ticket impreso con datos del negocio desde la BD.
- Cambiar el NIT en Configuración → la siguiente impresión y el PDF lo muestran sin tocar código.
- Reimpresión, ticket de domicilio y ticket de deuda.
- Respaldo de la BD (`mysqldump`) antes de aplicar el bloque del rediseño de `estructura.sql` en el PC de la tienda.

## Orden y tamaño estimado

| # | Fase | Riesgo | Tamaño |
|---|---|---|---|
| 1–2 | Tabla `negocio` + TicketService | bajo | pequeño — **empezar aquí**, da valor inmediato |
| 3–4 | Sistema visual + layout | medio (afecta todo) | mediano |
| 5 | Login | bajo | pequeño |
| 8 | Vender | alto (es la caja) | grande |
| 7, 9 | Productos y Ventas | medio | mediano |
| 6, 10 | Inicio y resto | bajo | mediano |
| 11 | Verificación | — | — |

Cada fase en su propio commit, para poder volver atrás si algo falla en la tienda.

## Decisiones pendientes

1. **Colores/marca**: propuesta "hoja y mazorca" arriba. Si el negocio tiene logo o colores propios, se reemplazan `--hoja` y `--mazorca` y el resto se mantiene.
2. **Rol admin**: hoy es `email == 'admin'`. ¿Agregar columna `rol` a `users` ahora o dejarlo así?
3. **Domicilios**: el menú está comentado. ¿Se rediseña o se deja oculto?
4. **Factura electrónica DIAN**: el campo `resolucion_dian` queda preparado, pero no se implementa facturación electrónica en este plan.
