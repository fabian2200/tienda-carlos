/*
 * Ayudas comunes de las pantallas (se carga en maestra.blade.php).
 *
 * 1) Filtrar una tabla mientras se escribe:
 *      <input data-filtrar-tabla="#miTabla">
 *    Oculta las filas de <tbody> que no contienen el texto (sin tildes ni mayúsculas).
 *    Si existe un elemento [data-filtro-vacio="#miTabla"], se muestra cuando no queda ninguna fila.
 *
 * 2) Confirmar antes de eliminar:
 *      <button data-eliminar="URL" data-titulo="¿Eliminar…?" data-texto="Explicación">
 *    Pregunta con SweetAlert y envía un formulario DELETE a URL.
 */
(function () {
    "use strict";

    const normalizar = (t) => String(t || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");

    document.querySelectorAll("[data-filtrar-tabla]").forEach((campo) => {
        const selector = campo.dataset.filtrarTabla;
        const tabla = document.querySelector(selector);
        if (!tabla) return;
        const filas = Array.from(tabla.tBodies[0].rows).filter((f) => !f.hasAttribute("data-sin-filtro"));
        const textos = filas.map((f) => normalizar(f.textContent));
        const vacio = document.querySelector('[data-filtro-vacio="' + selector + '"]');
        const contador = document.querySelector('[data-filtro-contador="' + selector + '"]');

        const aplicar = () => {
            const buscado = normalizar(campo.value.trim());
            let visibles = 0;
            filas.forEach((f, i) => {
                const ver = !buscado || textos[i].includes(buscado);
                f.hidden = !ver;
                if (ver) visibles++;
            });
            if (vacio) vacio.hidden = visibles > 0;
            if (contador) contador.textContent = visibles.toLocaleString("es-CO");
        };
        campo.addEventListener("input", aplicar);
        aplicar();
    });

    document.addEventListener("click", (e) => {
        const boton = e.target.closest("[data-eliminar]");
        if (!boton || typeof Swal === "undefined") return;
        e.preventDefault();
        Swal.fire({
            title: boton.dataset.titulo || "¿Eliminar?",
            text: boton.dataset.texto || "Esta acción no se puede deshacer.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "No, conservar",
            reverseButtons: true,
        }).then((r) => {
            if (!r.isConfirmed) return;
            const f = document.createElement("form");
            f.method = "post";
            f.action = boton.dataset.eliminar;
            const token = document.querySelector('meta[name="csrf-token"]');
            f.innerHTML = '<input type="hidden" name="_method" value="DELETE">' +
                '<input type="hidden" name="_token" value="' + (token ? token.content : "") + '">';
            document.body.appendChild(f);
            f.submit();
        });
    });
})();
