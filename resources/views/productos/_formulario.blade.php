{{-- Formulario compartido de crear y editar producto. Recibe $producto (null al crear) y $categorias. --}}
@php
    $p = $producto ?? null;
    $valor = function ($campo, $defecto = '') use ($p) { return old($campo, $p ? $p->$campo : $defecto); };
    // Números sin ceros de sobra (2901.00 → 2901); si viene vacío o con texto, tal cual
    $numero = function ($campo) use ($valor) { $v = $valor($campo); return is_numeric($v) ? $v + 0 : $v; };
    $unidad = $valor('unidad_medida', 'Unidades');
    $categoriaActual = $valor('categoria');
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Revisa estos datos:</strong>
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="formulario-producto">
    <div class="formulario-principal">
        <section class="panel">
            <h2 class="panel-titulo">Identificación</h2>
            <div class="form-group">
                <label for="codigo_barras">Código de barras</label>
                <input id="codigo_barras" name="codigo_barras" class="form-control input-grande cifra" required autocomplete="off"
                       value="{{ $valor('codigo_barras') }}" placeholder="Escanéalo o escríbelo" @if(!$p) autofocus @endif>
            </div>
            <div class="form-group mb-0">
                <label for="descripcion">Nombre del producto</label>
                <input id="descripcion" name="descripcion" class="form-control input-grande" required autocomplete="off"
                       value="{{ $valor('descripcion') }}" placeholder="Ej.: Arroz Diana 500 g">
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-titulo" id="tituloCategoria">Categoría</h2>
            <div class="opciones-chips" role="radiogroup" aria-labelledby="tituloCategoria">
                @foreach($categorias as $nombre => $icono)
                    <input type="radio" id="cat_{{ $loop->index }}" name="categoria" value="{{ $nombre }}" required @if($categoriaActual === $nombre) checked @endif>
                    <label class="opcion-chip" for="cat_{{ $loop->index }}"><i class="fas {{ $icono }}" aria-hidden="true"></i> {{ $nombre === 'Fritos' ? 'Fritos y jugos' : $nombre }}</label>
                @endforeach
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-titulo">Proveedor</h2>
            <div class="proveedor-elegir">
                <label class="oculto" for="id_proveedor">Proveedor</label>
                <select id="id_proveedor" name="id_proveedor" class="custom-select input-grande">
                    <option value="">Sin proveedor</option>
                    @foreach($proveedores as $proveedorOpcion)
                        <option value="{{ $proveedorOpcion->id }}" @if((string) $valor('id_proveedor') === (string) $proveedorOpcion->id) selected @endif>{{ $proveedorOpcion->nombre }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-secondary" id="botonNuevoProveedor"><i class="fas fa-plus" aria-hidden="true"></i> Nuevo proveedor</button>
            </div>
            <p class="ayuda-campo mb-0">A quién se le compra este producto. Sirve para filtrar el inventario por proveedor.</p>
        </section>

        <section class="panel">
            <h2 class="panel-titulo">Precio y existencia</h2>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    <label for="precio_compra">Precio de compra</label>
                    <input id="precio_compra" name="precio_compra" class="form-control input-grande cifra" required inputmode="decimal" autocomplete="off" value="{{ $numero('precio_compra') }}">
                </div>
                <div class="form-group col-sm-4">
                    <label for="porcentaje">Ganancia %</label>
                    <input id="porcentaje" class="form-control input-grande cifra" inputmode="decimal" autocomplete="off" placeholder="Ej.: 25">
                </div>
                <div class="form-group col-sm-4">
                    <label for="precio_venta">Precio de venta</label>
                    <input id="precio_venta" name="precio_venta" class="form-control input-grande cifra" required inputmode="decimal" autocomplete="off" value="{{ $numero('precio_venta') }}">
                </div>
            </div>
            <p class="ayuda-campo">Escribe la ganancia en porcentaje y se calcula el precio de venta (redondeado a 100), o escribe el precio y se calcula el porcentaje.</p>
            <div class="form-row mb-0">
                <div class="form-group col-sm-6 mb-0">
                    <label for="existencia">Existencia</label>
                    <input id="existencia" name="existencia" class="form-control input-grande cifra" required inputmode="decimal" autocomplete="off" value="{{ $numero('existencia') }}">
                </div>
                <div class="form-group col-sm-6 mb-0">
                    <label for="unidad_medida">Se vende por</label>
                    <select id="unidad_medida" name="unidad_medida" class="custom-select input-grande">
                        <option value="Unidades" @if($unidad === 'Unidades') selected @endif>Unidades</option>
                        <option value="Kilos" @if($unidad === 'Kilos') selected @endif>Kilos (se pesa)</option>
                        <option value="Libras" @if($unidad === 'Libras') selected @endif>Libras (se pesa)</option>
                    </select>
                </div>
            </div>
        </section>
    </div>

    <aside class="formulario-lateral">
        <section class="panel">
            <h2 class="panel-titulo">Foto</h2>
            <label class="zona-foto" for="imagen">
                @if($p && $p->imagen)
                    <img id="imagen_previa" src="{{ route('productos.imagen', $p) }}?v={{ optional($p->updated_at)->timestamp }}" alt="Foto actual del producto">
                @else
                    <img id="imagen_previa" alt="" hidden>
                    <span class="zona-foto-vacia" id="fotoVacia"><i class="fas fa-camera" aria-hidden="true"></i> Elegir foto</span>
                @endif
            </label>
            <input type="file" id="imagen" name="imagen" accept="image/*" class="oculto">
            <p class="ayuda-campo mb-0">Opcional. Se ve en el buscador de Vender.</p>
        </section>

        <div class="formulario-acciones">
            <button type="submit" class="btn btn-mazorca btn-block input-grande">{{ $p ? 'Guardar cambios' : 'Registrar producto' }}</button>
            <a class="btn btn-secondary btn-block" href="{{ route('productos.index') }}">{{ $p ? 'Cancelar' : 'Ir al listado' }}</a>
        </div>
    </aside>
</div>

{{-- Alta rápida de proveedor (sin <form>: está dentro del formulario del producto) --}}
<div class="modal fade" id="modalProveedor" tabindex="-1" role="dialog" aria-labelledby="tituloNuevoProveedor" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="tituloNuevoProveedor">Nuevo proveedor</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nuevoProveedorNombre">Nombre</label>
                    <input id="nuevoProveedorNombre" class="form-control input-grande" maxlength="150" autocomplete="off" placeholder="Ej.: Distribuidora Postobón">
                </div>
                <div class="form-group mb-0">
                    <label for="nuevoProveedorTelefono">Teléfono <small class="text-muted">(opcional)</small></label>
                    <input id="nuevoProveedorTelefono" class="form-control input-grande cifra" maxlength="40" autocomplete="off" inputmode="tel">
                </div>
                <p class="ayuda-campo mb-0 mt-2">Los demás datos se completan después en Proveedores.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarNuevoProveedor">Agregar y elegir</button>
            </div>
        </div>
    </div>
</div>

<script>
// Alta rápida de proveedor: lo crea por AJAX y lo deja elegido en el formulario
(function () {
    const $c = id => document.getElementById(id);
    $c("botonNuevoProveedor").addEventListener("click", () => {
        $c("nuevoProveedorNombre").value = "";
        $c("nuevoProveedorTelefono").value = "";
        $("#modalProveedor").modal("show");
    });
    $("#modalProveedor").on("shown.bs.modal", () => $c("nuevoProveedorNombre").focus());
    $c("nuevoProveedorNombre").addEventListener("keydown", e => { if (e.key === "Enter") { e.preventDefault(); $c("guardarNuevoProveedor").click(); } });

    $c("guardarNuevoProveedor").addEventListener("click", () => {
        const nombre = $c("nuevoProveedorNombre").value.trim();
        if (!nombre) return $c("nuevoProveedorNombre").focus();
        const boton = $c("guardarNuevoProveedor");
        boton.disabled = true;
        $.ajax({
            url: @json(route('proveedores.store')),
            method: "POST",
            headers: { Accept: "application/json" },
            data: { _token: document.querySelector('meta[name="csrf-token"]').content, nombre: nombre, telefono: $c("nuevoProveedorTelefono").value.trim() },
        }).then(r => {
            const opcion = new Option(r.nombre, r.id, true, true);
            $c("id_proveedor").add(opcion);
            $("#modalProveedor").modal("hide");
            Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Proveedor agregado: " + r.nombre, showConfirmButton: false, timer: 2500, customClass: { popup: "aviso-pos" } });
        }, x => {
            const errores = x.responseJSON && x.responseJSON.errors;
            Swal.fire({ icon: "error", title: "No se pudo agregar", text: errores ? Object.values(errores)[0][0] : "Revisa la conexión e inténtalo de nuevo." });
        }).always(() => (boton.disabled = false));
    });
})();
</script>

<script>
(function () {
    const $c = id => document.getElementById(id);
    // Precios: el punto separa miles; la coma es decimal
    const num = v => Number(String(v).replace(/\./g, "").replace(",", ".")) || 0;
    const redondear100 = n => Math.round(n / 100) * 100;

    function porcentajeDesdeVenta() {
        const compra = num($c("precio_compra").value), venta = num($c("precio_venta").value);
        $c("porcentaje").value = compra > 0 && venta > 0 ? String(Math.round(((venta - compra) / compra) * 1000) / 10).replace(".", ",") : "";
    }
    function ventaDesdePorcentaje() {
        const compra = num($c("precio_compra").value), p = $c("porcentaje").value;
        if (compra > 0 && p !== "") $c("precio_venta").value = redondear100(compra * (1 + num(p) / 100));
    }
    $c("precio_venta").addEventListener("input", porcentajeDesdeVenta);
    $c("porcentaje").addEventListener("input", ventaDesdePorcentaje);
    $c("precio_compra").addEventListener("input", () => ($c("porcentaje").value !== "" ? ventaDesdePorcentaje() : porcentajeDesdeVenta()));
    porcentajeDesdeVenta();

    // El servidor espera números con punto decimal
    $c("precio_compra").form.addEventListener("submit", () => {
        ["precio_compra", "precio_venta"].forEach(id => ($c(id).value = num($c(id).value)));
        $c("existencia").value = Number(String($c("existencia").value).replace(",", ".")) || 0;
    });

    // Vista previa de la foto
    $c("imagen").addEventListener("change", () => {
        const archivo = $c("imagen").files[0];
        if (!archivo) return;
        const lector = new FileReader();
        lector.onload = e => {
            $c("imagen_previa").src = e.target.result;
            $c("imagen_previa").hidden = false;
            if ($c("fotoVacia")) $c("fotoVacia").hidden = true;
        };
        lector.readAsDataURL(archivo);
    });

    // Al registrar, el cursor arranca en el código (algunos navegadores ignoran autofocus)
    if ($c("codigo_barras").hasAttribute("autofocus")) $c("codigo_barras").focus();

    // El lector de código de barras termina con Enter: que no envíe el formulario, que pase al nombre
    $c("codigo_barras").addEventListener("keydown", e => {
        if (e.key === "Enter") { e.preventDefault(); $c("descripcion").focus(); }
    });
})();
</script>
