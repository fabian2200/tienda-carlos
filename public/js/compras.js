/*
 * Registrar compra: proveedor con buscador, productos con cantidad y precios nuevos.
 * Al guardar, el servidor suma la cantidad a la existencia y actualiza los precios (ComprasController).
 */
(function ($) {
    "use strict";

    const form = document.getElementById("formCompra");
    if (!form) return;
    const $c = (id) => document.getElementById(id);
    const token = document.querySelector('meta[name="csrf-token"]').content;

    // ---------- números ----------
    const pesos = (n) => "$ " + Math.round(Number(n) || 0).toLocaleString("es-CO");
    // Precios: el punto separa miles (2.300) y la coma es decimal
    const leerPrecio = (v) => Number(String(v).replace(/\./g, "").replace(",", ".")) || 0;
    // Cantidades: punto o coma son decimales (1.5 kg = 1,5 kg)
    const leerCantidad = (v) => Number(String(v).replace(",", ".")) || 0;
    const escribirPrecio = (n) => (Number(n) || 0).toLocaleString("es-CO", { maximumFractionDigits: 2 });
    const esPeso = (p) => p.unidad_medida === "Kilos" || p.unidad_medida === "Libras";
    const abreviatura = (u) => (u === "Kilos" ? "kg" : u === "Libras" ? "lb" : "und");
    const escapar = (t) => String(t == null ? "" : t).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
    const normalizar = (t) => String(t || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");

    // ---------- estado ----------
    let catalogo = [];
    const lineas = []; // { producto, cantidad, compra, venta }

    // ---------- catálogo y buscador ----------
    const buscar = $c("buscarProducto");
    const sugerencias = $c("sugerencias");
    let resultados = [];
    let marcado = 0;

    $.getJSON(form.dataset.urlCatalogo).then((r) => {
        catalogo = r.map((p) => {
            const texto = normalizar(p.descripcion).replace(/\s+/g, " ").trim();
            return Object.assign(p, { _texto: texto, _palabras: texto.split(" ") });
        });
        restaurarLineas();
        buscar.focus();
    }, () => Swal.fire({ icon: "error", title: "No se pudo cargar la lista de productos", text: "Recarga la página e inténtalo de nuevo." }));

    function proveedorElegido() { return Number($("#id_proveedor").val()) || 0; }

    // Búsqueda por nombre (todas las palabras, en cualquier orden) o por código de barras.
    // Muchos nombres del inventario tienen espacios dobles ("agua brisa  18.9L"): se comparan palabra por palabra.
    function filtrar(texto) {
        const t = normalizar(texto).replace(/\s+/g, " ").trim();
        if (!t) return [];
        const palabras = t.split(" ");
        const prov = proveedorElegido();
        const puntaje = (p) => {
            const codigo = String(p.codigo_barras);
            if (codigo === texto.trim()) return 4;                         // código exacto
            if (p._texto.startsWith(palabras[0])) return 3;               // el nombre empieza con lo escrito
            if (p._palabras.some((w) => w.startsWith(palabras[0]))) return 2; // alguna palabra empieza igual
            return 1;
        };
        return catalogo
            .filter((p) => palabras.every((w) => p._texto.includes(w)) || String(p.codigo_barras).includes(t))
            .map((p) => ({ p: p, puntos: puntaje(p) + (prov && p.id_proveedor === prov ? 10 : 0) })) // los del proveedor elegido, primero
            .sort((a, b) => b.puntos - a.puntos || a.p._texto.localeCompare(b.p._texto))
            .slice(0, 8)
            .map((x) => x.p);
    }

    function pintarSugerencias() {
        if (!resultados.length) {
            sugerencias.innerHTML = buscar.value.trim() ? '<p class="sugerencia-vacia">Ningún producto coincide.</p>' : "";
        } else {
            const prov = proveedorElegido();
            sugerencias.innerHTML = resultados.map((p, i) =>
                '<button type="button" role="option" class="sugerencia' + (i === marcado ? " marcada" : "") + '" data-indice="' + i + '">' +
                    '<span class="sugerencia-nombre"><strong>' + escapar(p.descripcion) + "</strong><small>" + escapar(p.codigo_barras) +
                    (prov && p.id_proveedor === prov ? ' <span class="badge badge-success">De este proveedor</span>' : "") + "</small></span>" +
                    '<span class="sugerencia-datos cifra">' + pesos(p.precio_compra) + " / " + abreviatura(p.unidad_medida) +
                    "<small>Hay " + Number(p.existencia).toLocaleString("es-CO") + " " + abreviatura(p.unidad_medida) + "</small></span>" +
                "</button>").join("");
        }
        sugerencias.hidden = !buscar.value.trim();
        buscar.setAttribute("aria-expanded", String(!sugerencias.hidden));
    }

    buscar.addEventListener("input", () => { resultados = filtrar(buscar.value); marcado = 0; pintarSugerencias(); });
    buscar.addEventListener("keydown", (e) => {
        if (e.key === "ArrowDown" || e.key === "ArrowUp") {
            e.preventDefault();
            if (!resultados.length) return;
            marcado = (marcado + (e.key === "ArrowDown" ? 1 : -1) + resultados.length) % resultados.length;
            pintarSugerencias();
        } else if (e.key === "Enter") {
            e.preventDefault();
            // El lector de código termina con Enter: si el código existe exacto, se agrega directo
            const exacto = catalogo.find((p) => String(p.codigo_barras) === buscar.value.trim());
            const elegido = exacto || resultados[marcado];
            if (elegido) agregar(elegido);
            else if (buscar.value.trim()) Swal.fire({ toast: true, position: "top-end", icon: "error", title: "No hay ningún producto con ese código o nombre.", showConfirmButton: false, timer: 2500, customClass: { popup: "aviso-pos" } });
        } else if (e.key === "Escape") {
            buscar.value = ""; resultados = []; pintarSugerencias();
        }
    });
    sugerencias.addEventListener("mousedown", (e) => e.preventDefault()); // que el clic no quite el foco antes de elegir
    sugerencias.addEventListener("click", (e) => {
        const b = e.target.closest(".sugerencia");
        if (b) agregar(resultados[Number(b.dataset.indice)]);
    });
    buscar.addEventListener("blur", () => setTimeout(() => { sugerencias.hidden = true; }, 150));
    buscar.addEventListener("focus", () => { if (buscar.value.trim()) pintarSugerencias(); });

    // ---------- líneas de la compra ----------
    function agregar(producto, valores) {
        buscar.value = ""; resultados = []; pintarSugerencias();
        const existente = lineas.findIndex((l) => l.producto.id === producto.id);
        if (existente !== -1 && !valores) {
            // Ya está: se enfoca su cantidad en vez de repetirlo
            const campo = document.querySelector('[data-linea="' + existente + '"] .js-cantidad');
            campo.focus(); campo.select();
            return;
        }
        // compra y venta se guardan siempre como números (el servidor los manda como texto "2300.00")
        lineas.push({
            producto: producto,
            cantidad: valores ? valores.cantidad : "",
            compra: Number(valores ? valores.precio_compra : producto.precio_compra) || 0,
            venta: Number(valores ? valores.precio_venta : producto.precio_venta) || 0,
        });
        pintarLineas();
        if (!valores) {
            const campo = document.querySelector('[data-linea="' + (lineas.length - 1) + '"] .js-cantidad');
            campo.focus();
        }
    }

    function ganancia(compra, venta) {
        if (!(compra > 0)) return "";
        const p = Math.round(((venta - compra) / compra) * 1000) / 10;
        return "Ganancia " + String(p).replace(".", ",") + " %";
    }

    function pintarLineas() {
        $c("lineasCompra").innerHTML = lineas.map((l, i) => {
            const p = l.producto, u = abreviatura(p.unidad_medida);
            const cambioCompra = l.compra !== Number(p.precio_compra);
            const cambioVenta = l.venta !== Number(p.precio_venta);
            return '<tr data-linea="' + i + '">' +
                '<td class="prod"><strong>' + escapar(p.descripcion) + '</strong><small><span class="cifra">' + escapar(p.codigo_barras) + '</span> <span class="etiqueta-categoria">Hay ' + Number(p.existencia).toLocaleString("es-CO") + " " + u + "</span></small></td>" +
                '<td class="num"><input class="form-control cifra campo-linea js-cantidad" inputmode="decimal" value="' + escapar(l.cantidad) + '" placeholder="0" aria-label="Cantidad de ' + escapar(p.descripcion) + ' en ' + u + '">' +
                    '<small><span class="unidad-fija" title="Unidad del producto: no se cambia desde la compra"><i class="fas fa-lock" aria-hidden="true"></i> ' + escapar(p.unidad_medida) + "</span></small></td>" +
                '<td class="num"><input class="form-control cifra campo-linea js-compra' + (cambioCompra ? " cambiado" : "") + '" inputmode="decimal" value="' + escapar(escribirPrecio(l.compra)) + '" aria-label="Precio de compra de ' + escapar(p.descripcion) + '">' +
                    '<small class="text-muted">Antes ' + pesos(p.precio_compra) + "</small></td>" +
                '<td class="num"><input class="form-control cifra campo-linea js-venta' + (cambioVenta ? " cambiado" : "") + '" inputmode="decimal" value="' + escapar(escribirPrecio(l.venta)) + '" aria-label="Precio de venta de ' + escapar(p.descripcion) + '">' +
                    '<small class="text-muted js-ganancia">' + ganancia(l.compra, l.venta) + "</small></td>" +
                '<td class="num"><strong class="js-subtotal">' + pesos(leerCantidad(l.cantidad) * l.compra) + "</strong></td>" +
                '<td class="num"><button type="button" class="btn-accion peligro js-quitar" aria-label="Quitar ' + escapar(p.descripcion) + '"><i class="fas fa-times"></i></button></td>' +
            "</tr>";
        }).join("");
        $c("compraVacia").hidden = lineas.length > 0;
        actualizarTotales();
    }

    // Cambios en una línea: se actualizan subtotal, ganancia y total sin redibujar (no se pierde el cursor)
    $c("lineasCompra").addEventListener("input", (e) => {
        const fila = e.target.closest("tr");
        if (!fila) return;
        const l = lineas[Number(fila.dataset.linea)];
        if (e.target.classList.contains("js-cantidad")) l.cantidad = e.target.value;
        if (e.target.classList.contains("js-compra")) { l.compra = leerPrecio(e.target.value); e.target.classList.toggle("cambiado", l.compra !== Number(l.producto.precio_compra)); }
        if (e.target.classList.contains("js-venta")) { l.venta = leerPrecio(e.target.value); e.target.classList.toggle("cambiado", l.venta !== Number(l.producto.precio_venta)); }
        fila.querySelector(".js-subtotal").textContent = pesos(leerCantidad(l.cantidad) * l.compra);
        fila.querySelector(".js-ganancia").textContent = ganancia(l.compra, l.venta);
        actualizarTotales();
    });
    // Al salir de un precio, se muestra con puntos de miles
    $c("lineasCompra").addEventListener("change", (e) => {
        if (e.target.classList.contains("js-compra") || e.target.classList.contains("js-venta")) e.target.value = escribirPrecio(leerPrecio(e.target.value));
    });
    // Enter en una línea: vuelve al buscador para el siguiente producto
    $c("lineasCompra").addEventListener("keydown", (e) => {
        if (e.key === "Enter" && e.target.classList.contains("campo-linea")) { e.preventDefault(); buscar.focus(); }
    });
    $c("lineasCompra").addEventListener("click", (e) => {
        const b = e.target.closest(".js-quitar");
        if (!b) return;
        lineas.splice(Number(b.closest("tr").dataset.linea), 1);
        pintarLineas();
        buscar.focus();
    });

    function totalLineas() {
        return lineas.reduce((s, l) => s + leerCantidad(l.cantidad) * l.compra, 0);
    }

    function actualizarTotales() {
        const hay = lineas.length > 0;
        $c("totalManual").hidden = hay;
        $c("totalAuto").hidden = !hay;
        $c("etiquetaTotal").textContent = hay ? "Total de la compra" : "Valor pagado";
        $c("ayudaTotal").textContent = hay ? "Se calcula con la cantidad y el precio de compra de cada producto." : "Sin productos, escribe cuánto se pagó.";
        $c("totalAuto").textContent = pesos(totalLineas());
        const efecto = $c("compraEfecto");
        efecto.hidden = !hay;
        if (hay) {
            const cambios = lineas.filter((l) => l.compra !== Number(l.producto.precio_compra) || l.venta !== Number(l.producto.precio_venta)).length;
            efecto.innerHTML = '<i class="fas fa-boxes" aria-hidden="true"></i> Se sumará la existencia de <strong>' + lineas.length + (lineas.length === 1 ? " producto" : " productos") + "</strong>" +
                (cambios ? " y cambiará el precio de <strong>" + cambios + "</strong>." : ". Los precios quedan igual.");
        }
    }

    $c("totalManual").addEventListener("input", (e) => {
        const v = Number(e.target.value.replace(/\D/g, "")) || 0;
        e.target.value = v ? v.toLocaleString("es-CO") : "";
        $c("totalNumero").value = v || "";
    });

    // Si el servidor devolvió un error, se vuelven a armar las líneas que se habían escrito
    function restaurarLineas() {
        let previas = [];
        try { previas = JSON.parse(form.dataset.lineasPrevias || "[]"); } catch (e) {}
        Object.values(previas || {}).forEach((v) => {
            const p = catalogo.find((x) => String(x.id) === String(v.id));
            if (p) agregar(p, { cantidad: String(v.cantidad || "").replace(".", ","), precio_compra: Number(v.precio_compra) || 0, precio_venta: Number(v.precio_venta) || 0 });
        });
    }

    // ---------- guardar ----------
    form.addEventListener("submit", (e) => {
        const aviso = (texto, foco) => { e.preventDefault(); Swal.fire({ icon: "warning", title: "Falta un dato", text: texto }).then(() => foco && foco.focus()); };
        if (!proveedorElegido()) return aviso("Elige el proveedor de la compra.", null);

        if (!lineas.length) {
            if (!(Number($c("totalNumero").value) > 0)) return aviso("Escribe el valor pagado o agrega los productos que compraste.", $c("totalManual"));
        }
        for (let i = 0; i < lineas.length; i++) {
            const l = lineas[i], cant = leerCantidad(l.cantidad);
            const fila = document.querySelector('[data-linea="' + i + '"]');
            if (!(cant > 0)) return aviso("Escribe la cantidad que llegó de " + l.producto.descripcion + ".", fila.querySelector(".js-cantidad"));
            if (!esPeso(l.producto) && Math.floor(cant) !== cant) return aviso(l.producto.descripcion + " se vende por unidades: la cantidad debe ser un número entero.", fila.querySelector(".js-cantidad"));
            if (!(l.venta > 0)) return aviso("Escribe el precio de venta de " + l.producto.descripcion + ".", fila.querySelector(".js-venta"));
        }

        // Campos que recibe el servidor: productos[i][id|cantidad|precio_compra|precio_venta]
        $c("camposProductos").innerHTML = lineas.map((l, i) =>
            ["id", "cantidad", "precio_compra", "precio_venta"].map((campo) => {
                const valor = campo === "id" ? l.producto.id : campo === "cantidad" ? leerCantidad(l.cantidad) : campo === "precio_compra" ? l.compra : l.venta;
                return '<input type="hidden" name="productos[' + i + "][" + campo + ']" value="' + escapar(valor) + '">';
            }).join("")).join("");
        if (lineas.length) $c("totalNumero").value = "";
        $c("botonGuardar").disabled = true;
        $c("botonGuardar").textContent = "Guardando…";
    });

    // ---------- proveedor ----------
    $("#id_proveedor").selectpicker();
    $("#id_proveedor").on("changed.bs.select change", () => { if (buscar.value.trim()) { resultados = filtrar(buscar.value); pintarSugerencias(); } });

    $c("botonNuevoProveedor").addEventListener("click", () => {
        $c("nuevoProveedorNombre").value = "";
        $c("nuevoProveedorTelefono").value = "";
        $("#modalProveedor").modal("show");
    });
    $("#modalProveedor").on("shown.bs.modal", () => $c("nuevoProveedorNombre").focus());
    $c("nuevoProveedorNombre").addEventListener("keydown", (e) => { if (e.key === "Enter") { e.preventDefault(); $c("guardarNuevoProveedor").click(); } });
    $c("guardarNuevoProveedor").addEventListener("click", () => {
        const nombre = $c("nuevoProveedorNombre").value.trim();
        if (!nombre) return $c("nuevoProveedorNombre").focus();
        const boton = $c("guardarNuevoProveedor");
        boton.disabled = true;
        $.ajax({
            url: form.dataset.urlProveedor, method: "POST", headers: { Accept: "application/json" },
            data: { _token: token, nombre: nombre, telefono: $c("nuevoProveedorTelefono").value.trim() },
        }).then((r) => {
            $("#id_proveedor").append(new Option(r.nombre, r.id, true, true)).selectpicker("refresh").selectpicker("val", String(r.id));
            $("#modalProveedor").modal("hide");
        }, (x) => {
            const errores = x.responseJSON && x.responseJSON.errors;
            Swal.fire({ icon: "error", title: "No se pudo agregar", text: errores ? Object.values(errores)[0][0] : "Revisa la conexión e inténtalo de nuevo." });
        }).always(() => (boton.disabled = false));
    });

    pintarLineas();
})(jQuery);
