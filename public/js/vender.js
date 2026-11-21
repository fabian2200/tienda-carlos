/*
 * Pantalla Vender: todo en una sola ventana (carrito a la izquierda, tirilla de cobro a la derecha).
 * Usa los mismos endpoints de siempre (VenderController, ProductosController, BalanzaController).
 * La configuración (rutas y token) viene en data-* del elemento #vender.
 */
(function ($) {
    "use strict";

    const raiz = document.getElementById("vender");
    if (!raiz) return;

    const cfg = {
        token: raiz.dataset.token,
        carrito: raiz.dataset.urlCarrito,
        agregar: raiz.dataset.urlAgregar,
        actualizar: raiz.dataset.urlActualizar,
        terminar: raiz.dataset.urlTerminar,
        unidad: raiz.dataset.urlUnidad,
        buscar: raiz.dataset.urlBuscar,
        peso: raiz.dataset.urlPeso,
        imagenes: raiz.dataset.urlImagenes,
    };

    // ---------- utilidades ----------
    const $id = (id) => document.getElementById(id);
    const pesos = (n) => "$ " + Math.round(Number(n) || 0).toLocaleString("es-CO");
    const redondear100 = (n) => Math.round(n / 100) * 100;
    const soloDigitos = (t) => Number(String(t || "").replace(/\D/g, "")) || 0;
    const esPeso = (p) => p.unidad_medida === "Kilos" || p.unidad_medida === "Libras";
    const abreviatura = (u) => (u === "Kilos" ? "kg" : u === "Libras" ? "lb" : "und");
    const escapar = (t) => String(t == null ? "" : t).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
    const cantidadTexto = (p) => {
        const n = Number(p.cantidad) || 0;
        return esPeso(p)
            ? n.toLocaleString("es-CO", { minimumFractionDigits: 2, maximumFractionDigits: 3 }) + " " + abreviatura(p.unidad_medida)
            : n.toLocaleString("es-CO");
    };

    function aviso(mensaje, icono) {
        Swal.fire({
            toast: true, position: "top-end", icon: icono || "success", title: mensaje,
            showConfirmButton: false, timer: icono === "error" ? 4000 : 2200, timerProgressBar: true,
            customClass: { popup: "aviso-pos" },
        });
    }

    // Las peticiones que cambian el carrito van en fila: si se escanea muy rápido
    // no se pisan entre sí en la sesión del servidor.
    let fila = Promise.resolve();
    function enFila(tarea) {
        fila = fila.then(tarea, tarea);
        return fila;
    }

    // ---------- estado ----------
    let productos = [];
    let total = 0;
    let resaltarCodigo = null;
    let cobrando = false;

    const codigo = $id("codigo");
    const recibido = $id("recibido");
    const fiar = $id("fiar");
    const imprimir = $id("imprimir");

    function enfocarCodigo() {
        if (document.activeElement !== recibido) codigo.focus();
    }

    // ---------- carrito ----------
    function cargarCarrito() {
        return $.getJSON(cfg.carrito).then((r) => {
            productos = r.productosCarrito || [];
            total = Number(r.total) || 0;
            pintarCarrito();
            actualizarCobro();
        });
    }

    function pintarCarrito() {
        const filas = $id("filas");
        if (!productos.length) {
            filas.innerHTML = '<tr class="carrito-vacio"><td colspan="5">Escanea el primer producto para empezar la venta.</td></tr>';
        } else {
            filas.innerHTML = productos.map((p, i) => {
                const peso = esPeso(p);
                const cantidad = peso
                    ? '<span class="cantidad-fija">' + cantidadTexto(p) + "</span>"
                    : '<span class="cantidad" data-indice="' + i + '">' +
                        '<button type="button" class="js-menos" aria-label="Uno menos de ' + escapar(p.descripcion) + '"' + (Number(p.cantidad) <= 1 ? " disabled" : "") + ">−</button>" +
                        '<input type="text" inputmode="numeric" class="js-cantidad" value="' + escapar(p.cantidad) + '" aria-label="Cantidad de ' + escapar(p.descripcion) + '">' +
                        '<button type="button" class="js-mas" aria-label="Uno más de ' + escapar(p.descripcion) + '">+</button>' +
                      "</span>";
                return '<tr class="' + (p.codigo_barras === resaltarCodigo ? "reciente" : "") + '">' +
                    '<td class="prod"><strong>' + escapar(p.descripcion) + "</strong><small>" + escapar(p.codigo_barras) + "</small></td>" +
                    '<td class="num">' + pesos(p.precio_venta) + (peso ? " /" + abreviatura(p.unidad_medida) : "") + "</td>" +
                    '<td class="centro">' + cantidad + "</td>" +
                    '<td class="num"><strong>' + pesos(p.precio_total) + "</strong></td>" +
                    '<td class="num"><button type="button" class="btn-accion peligro js-quitar" data-indice="' + i + '" aria-label="Quitar ' + escapar(p.descripcion) + '"><i class="fas fa-times"></i></button></td>' +
                    "</tr>";
            }).join("");
        }
        resaltarCodigo = null;

        const unidades = productos.reduce((s, p) => s + (esPeso(p) ? 1 : Number(p.cantidad) || 0), 0);
        $id("cuenta").textContent = productos.length
            ? productos.length + (productos.length === 1 ? " producto, " : " productos, ") + unidades + (unidades === 1 ? " unidad" : " unidades")
            : "";
        $id("totalVenta").textContent = pesos(total);
        $id("articulos").textContent = productos.length === 1 ? "1 producto" : productos.length + " productos";
    }

    function agregar(codigoBarras, cantidad) {
        return enFila(() => $.post(cfg.agregar, { _token: cfg.token, codigo: codigoBarras, cantidad: cantidad }).then((r) => {
            if (r.status === "error") {
                aviso(r.message, "error");
                codigo.classList.add("con-error");
                setTimeout(() => codigo.classList.remove("con-error"), 900);
                return;
            }
            resaltarCodigo = codigoBarras;
            return cargarCarrito();
        }));
    }

    function cambiarCantidad(indice, cantidad) {
        const p = productos[indice];
        if (!p || cantidad < 1) return;
        enFila(() => $.post(cfg.actualizar, { _token: cfg.token, codigo: p.codigo_barras, indice: indice, cantidad: cantidad }).then((r) => {
            if (r.status === "error") aviso(r.message, "error");
            return cargarCarrito();
        })).then(enfocarCodigo);
    }

    function quitar(indice) {
        enFila(() => $.post(cfg.agregar, { _token: cfg.token, _method: "DELETE", indice: indice }).then(cargarCarrito, cargarCarrito))
            .then(enfocarCodigo);
    }

    // ---------- escanear ----------
    $id("formCodigo").addEventListener("submit", (e) => {
        e.preventDefault();
        const valor = codigo.value.trim();
        if (!valor) return;
        codigo.value = "";
        $.get(cfg.unidad, { codigo: valor }).then((p) => {
            if (p && esPeso(p)) {
                abrirProducto(p, "pesar");
            } else {
                agregar(valor, 1); // si no existe, el servidor responde "Producto no encontrado"
            }
        }, () => aviso("No se pudo consultar el producto. Revisa la conexión.", "error"));
    });

    $id("filas").addEventListener("click", (e) => {
        const menos = e.target.closest(".js-menos"), mas = e.target.closest(".js-mas"), fuera = e.target.closest(".js-quitar");
        if (fuera) return quitar(Number(fuera.dataset.indice));
        const caja = e.target.closest(".cantidad");
        if (!caja) return;
        const i = Number(caja.dataset.indice), actual = Number(productos[i].cantidad) || 1;
        if (menos) cambiarCantidad(i, actual - 1);
        if (mas) cambiarCantidad(i, actual + 1);
    });
    $id("filas").addEventListener("change", (e) => {
        if (!e.target.classList.contains("js-cantidad")) return;
        const i = Number(e.target.closest(".cantidad").dataset.indice);
        const nueva = soloDigitos(e.target.value);
        if (nueva >= 1) cambiarCantidad(i, nueva); else pintarCarrito();
    });
    $id("filas").addEventListener("keydown", (e) => {
        if (e.key === "Enter" && e.target.classList.contains("js-cantidad")) { e.preventDefault(); e.target.blur(); }
    });

    // ---------- buscar / pesar (ventana de producto) ----------
    const modal = $("#modalProducto");
    let catalogo = null;
    let elegido = null;

    function abrirBuscador() {
        $id("modalProducto").classList.remove("solo-detalle");
        mostrarDetalle(null);
        $id("buscarTexto").value = "";
        modal.modal("show");
        const listo = catalogo ? $.Deferred().resolve(catalogo) : $.getJSON(cfg.buscar).then((r) => (catalogo = r));
        $id("resultados").innerHTML = '<p class="buscador-nota">Cargando productos…</p>';
        listo.then(() => filtrar(""));
    }

    // Nombre sin tildes, mayúsculas ni espacios dobles (muchos productos los tienen: "agua brisa  18.9L")
    const normalizar = (t) => String(t || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "").replace(/\s+/g, " ").trim();

    // Búsqueda por nombre (todas las palabras, en cualquier orden) o por código de barras
    function filtrar(texto) {
        const t = normalizar(texto);
        const palabras = t ? t.split(" ") : [];
        const lista = (catalogo || [])
            .filter((p) => {
                if (!t) return true;
                if (p._texto === undefined) p._texto = normalizar(p.descripcion);
                return palabras.every((w) => p._texto.includes(w)) || String(p.codigo_barras).includes(t);
            })
            .sort((a, b) => (t ? Number(b._texto.startsWith(palabras[0])) - Number(a._texto.startsWith(palabras[0])) : 0))
            .slice(0, 60);
        $id("resultados").innerHTML = lista.length
            ? lista.map((p) =>
                '<button type="button" class="resultado" data-codigo="' + escapar(p.codigo_barras) + '">' +
                    (Number(p.tiene_imagen) ? '<img loading="lazy" src="' + cfg.imagenes + "/" + p.id + "/imagen?v=" + encodeURIComponent(p.updated_at || "") + '" alt="">' : '<span class="resultado-sin-foto"><i class="fas fa-shopping-basket"></i></span>') +
                    '<span class="resultado-nombre">' + escapar(p.descripcion) + "<small>" + escapar(p.codigo_barras) + "</small></span>" +
                    '<span class="resultado-precio cifra">' + pesos(p.precio_venta) + (esPeso(p) ? " /" + abreviatura(p.unidad_medida) : "") +
                        '<small class="' + (Number(p.existencia) <= 0 ? "agotado" : "") + '">' + Number(p.existencia).toLocaleString("es-CO") + " " + abreviatura(p.unidad_medida) + "</small></span>" +
                "</button>").join("")
            : '<p class="buscador-nota">No hay productos con “' + escapar(texto) + '”.</p>';
    }

    function abrirProducto(p, modo) {
        if (modo === "pesar") {
            $id("modalProducto").classList.add("solo-detalle");
            modal.modal("show");
        }
        mostrarDetalle(p);
    }

    function mostrarDetalle(p) {
        elegido = p;
        const detalle = $id("formDetalle");
        detalle.hidden = !p;
        $id("detalleVacio").hidden = !!p;
        if (!p) return;
        const peso = esPeso(p);
        $id("detalleNombre").textContent = p.descripcion;
        $id("detallePrecio").textContent = pesos(p.precio_venta) + " por " + (peso ? (p.unidad_medida === "Kilos" ? "kilo" : "libra") : "unidad");
        $id("etiquetaCantidad").textContent = peso ? "Peso en " + p.unidad_medida.toLowerCase() : "Cantidad";
        $id("botonBalanza").hidden = !peso;
        $id("detalleCantidad").value = peso ? "" : "1";
        $id("detalleValor").value = peso ? "" : Number(p.precio_venta).toLocaleString("es-CO");
        // Si la ventana ya está abierta (se eligió de la lista) el foco va ya; si no, al terminar de abrirse
        if ($id("modalProducto").classList.contains("show")) $id("detalleCantidad").select();
    }

    $id("resultados").addEventListener("click", (e) => {
        const b = e.target.closest(".resultado");
        if (!b) return;
        const p = (catalogo || []).find((x) => String(x.codigo_barras) === b.dataset.codigo);
        if (p) mostrarDetalle(p);
    });
    $id("buscarTexto").addEventListener("input", (e) => filtrar(e.target.value));
    $id("buscarTexto").addEventListener("keydown", (e) => {
        if (e.key === "Enter") { e.preventDefault(); const primero = $id("resultados").querySelector(".resultado"); if (primero) primero.click(); }
    });

    // Cantidad ⇄ valor: escribir uno calcula el otro
    $id("detalleCantidad").addEventListener("input", (e) => {
        if (!elegido) return;
        const n = Number(String(e.target.value).replace(",", ".")) || 0;
        $id("detalleValor").value = n ? redondear100(n * elegido.precio_venta).toLocaleString("es-CO") : "";
    });
    $id("detalleValor").addEventListener("input", (e) => {
        if (!elegido) return;
        const v = soloDigitos(e.target.value);
        e.target.value = v ? v.toLocaleString("es-CO") : "";
        const n = v / Number(elegido.precio_venta);
        $id("detalleCantidad").value = v ? (esPeso(elegido) ? String(parseFloat(n.toFixed(3))).replace(".", ",") : String(Math.max(1, Math.round(n)))) : "";
    });

    function leerBalanza() {
        if (!elegido || !esPeso(elegido)) return;
        const boton = $id("botonBalanza");
        boton.disabled = true;
        $.getJSON(cfg.peso).then((dato) => {
            const n = parseFloat(String(dato).replace(",", "."));
            if (!n) return aviso("La balanza no envió un peso. Escríbelo a mano.", "error");
            $id("detalleCantidad").value = String(n).replace(".", ",");
            $id("detalleCantidad").dispatchEvent(new Event("input"));
        }, () => aviso("No se pudo leer la balanza. Escribe el peso a mano.", "error"))
            .always(() => (boton.disabled = false));
    }
    $id("botonBalanza").addEventListener("click", leerBalanza);

    $id("formDetalle").addEventListener("submit", (e) => {
        e.preventDefault();
        if (!elegido) return;
        const n = Number(String($id("detalleCantidad").value).replace(",", ".")) || 0;
        if (n <= 0) {
            aviso(esPeso(elegido) ? "Escribe el peso o el valor a vender." : "Escribe la cantidad o el valor a vender.", "error");
            return $id("detalleCantidad").focus();
        }
        const p = elegido;
        cerrarModal();
        agregar(p.codigo_barras, esPeso(p) ? parseFloat(n.toFixed(3)) : Math.round(n));
    });

    // Bootstrap ignora "hide" mientras la ventana se está abriendo (escanear y confirmar muy rápido):
    // en ese caso se cierra apenas termine de abrirse.
    function cerrarModal() {
        const instancia = modal.data("bs.modal");
        if (instancia && instancia._isTransitioning && instancia._isShown) {
            modal.one("shown.bs.modal", () => modal.modal("hide"));
        } else {
            modal.modal("hide");
        }
    }

    // Bootstrap mueve el foco a la ventana al abrirla: lo ponemos después en el campo correcto
    modal.on("shown.bs.modal", () => {
        if (elegido) $id("detalleCantidad").select(); else $id("buscarTexto").focus();
    });
    modal.on("hidden.bs.modal", () => { elegido = null; codigo.focus(); });

    // ---------- cobro ----------
    function actualizarCobro() {
        const r = soloDigitos(recibido.value);
        const caja = $id("cambio"), etiqueta = $id("etiquetaCambio");
        caja.classList.remove("falta", "fiado");
        if (!r) { etiqueta.textContent = "Cambio"; caja.textContent = pesos(0); return; }
        const diferencia = r - total;
        if (diferencia >= 0) { etiqueta.textContent = "Cambio"; caja.textContent = pesos(diferencia); }
        else if (fiar.checked) { etiqueta.textContent = "Queda fiado"; caja.textContent = pesos(-diferencia); caja.classList.add("fiado"); }
        else { etiqueta.textContent = "Falta"; caja.textContent = pesos(-diferencia); caja.classList.add("falta"); }
    }

    recibido.addEventListener("input", () => {
        const v = soloDigitos(recibido.value);
        recibido.value = v ? v.toLocaleString("es-CO") : "";
        actualizarCobro();
    });
    recibido.addEventListener("keydown", (e) => {
        if (e.key === "Escape") { e.preventDefault(); codigo.focus(); }
    });
    fiar.addEventListener("change", actualizarCobro);

    document.querySelectorAll(".js-billete").forEach((b) => b.addEventListener("click", () => {
        const v = Number(b.dataset.valor) || total;
        recibido.value = v ? v.toLocaleString("es-CO") : "";
        actualizarCobro();
        $id("botonCobrar").focus();
    }));

    // "Imprimir ticket" recuerda la última elección en este navegador
    try { const guardado = localStorage.getItem("vender-imprimir"); if (guardado !== null) imprimir.checked = guardado === "1"; } catch (e) {}
    imprimir.addEventListener("change", () => { try { localStorage.setItem("vender-imprimir", imprimir.checked ? "1" : "0"); } catch (e) {} });

    // Cliente por defecto: la primera opción con valor (bootstrap-select puede agregar una opción de título vacía)
    const clientePorDefecto = $("#cliente option").filter(function () { return this.value; }).first().val();

    function reiniciarCobro() {
        recibido.value = "";
        fiar.checked = false;
        $("#cliente").selectpicker("val", clientePorDefecto);
        actualizarCobro();
    }

    function cobrar() {
        if (cobrando) return;
        if (!productos.length) { aviso("Agrega al menos un producto antes de cobrar.", "error"); return codigo.focus(); }

        // Sin valor recibido se toma como pago exacto
        const dinero = soloDigitos(recibido.value) || total;
        const falta = total - dinero;
        if (falta > 0 && !fiar.checked) {
            aviso("Falta " + pesos(falta) + ". Activa “Fiar lo que falta” o corrige lo recibido.", "error");
            return recibido.focus();
        }
        const idCliente = $("#cliente").val();
        if (!idCliente) { aviso("Elige el cliente de la venta.", "error"); return $("#cliente").selectpicker("toggle"); }
        if (falta > 0 && idCliente === clientePorDefecto) {
            aviso("Para fiar, elige el cliente que queda debiendo.", "error");
            return $("#cliente").selectpicker("toggle");
        }

        // Mismo formato que la pantalla anterior: vueltos = recibido − total (negativo cuando hay fiado)
        const datos = {
            _token: cfg.token,
            accion: "terminar",
            id_cliente: idCliente,
            total_pagar: total,
            total_dinero: dinero,
            total_vueltos: dinero - total,
            total_fiado: falta > 0 ? falta : 0,
            imprimir_factura: imprimir.checked ? "si" : "no",
        };

        cobrando = true;
        $id("botonCobrar").disabled = true;
        Swal.fire({ title: "Guardando la venta…", allowOutsideClick: false, showConfirmButton: false, willOpen: () => Swal.showLoading() });

        enFila(() => $.post(cfg.terminar, datos).then(() => {
            Swal.close();
            aviso(imprimir.checked ? "Venta cobrada. Imprimiendo ticket." : "Venta cobrada.");
            reiniciarCobro();
            return cargarCarrito();
        }, () => {
            Swal.fire({
                icon: "error",
                title: "No se pudo terminar el cobro",
                html: "Puede que la venta <b>sí se haya guardado</b> y fallara la impresora.<br>Revisa la lista de Ventas antes de volver a cobrar, para no duplicarla.",
                confirmButtonText: "Entendido",
            });
            return cargarCarrito();
        })).then(() => {
            cobrando = false;
            $id("botonCobrar").disabled = false;
            codigo.focus();
        });
    }

    $id("formCobro").addEventListener("submit", (e) => { e.preventDefault(); cobrar(); });

    $id("botonCancelar").addEventListener("click", () => {
        if (!productos.length) return codigo.focus();
        Swal.fire({
            title: "¿Cancelar esta venta?", text: "Se quitan todos los productos del carrito.", icon: "warning",
            showCancelButton: true, confirmButtonText: "Sí, cancelar venta", cancelButtonText: "No, seguir vendiendo", reverseButtons: true,
        }).then((r) => {
            if (!r.isConfirmed) return codigo.focus();
            enFila(() => $.post(cfg.terminar, { _token: cfg.token, accion: "cancelar" }).then(() => {
                aviso("Venta cancelada.");
                reiniciarCobro();
                return cargarCarrito();
            })).then(() => codigo.focus());
        });
    });

    $id("botonBuscar").addEventListener("click", abrirBuscador);

    // ---------- atajos ----------
    document.addEventListener("keydown", (e) => {
        const modalAbierto = $id("modalProducto").classList.contains("show");
        if (e.key === "F2") { e.preventDefault(); if (!modalAbierto) abrirBuscador(); }
        else if (e.key === "F8") { e.preventDefault(); if (modalAbierto) leerBalanza(); }
        else if (e.key === "F4" && !modalAbierto) { e.preventDefault(); recibido.focus(); recibido.select(); }
        else if (e.key === "F9" && !modalAbierto) { e.preventDefault(); cobrar(); }
        else if (e.key === "Delete" && document.activeElement === codigo && !codigo.value && productos.length) { e.preventDefault(); quitar(0); }
    });

    // ---------- inicio ----------
    $(".selectpicker").selectpicker();
    cargarCarrito().always(() => codigo.focus());
})(jQuery);
