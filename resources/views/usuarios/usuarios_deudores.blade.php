@extends("maestra")
@section("titulo", "Fiados")
@section("contenido")
<div class="pagina">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">Fiados</h1>
            <p class="subtitulo-pagina"><span data-filtro-contador="#tablaDeudores">{{ count($clientes_deudores) }}</span> clientes deben dinero</p>
        </div>
    </div>

    @include("notificacion")

    <section class="inicio-cifras cifras-ventas" aria-label="Resumen de fiados">
        <div class="inicio-cifra principal">
            <span class="etiqueta">Por cobrar</span>
            <span class="valor cifra">{{ dinero($total_por_cobrar) }}</span>
            <span class="detalle">Fiado menos abonos</span>
        </div>
        <div class="inicio-cifra">
            <span class="etiqueta">Total fiado</span>
            <span class="valor cifra">{{ dinero($total_fiado) }}</span>
            <span class="detalle">En ventas aún sin pagar</span>
        </div>
        <div class="inicio-cifra">
            <span class="etiqueta">Total abonado</span>
            <span class="valor cifra">{{ dinero($total_abonado_clientes) }}</span>
            <span class="detalle">Pagos parciales recibidos</span>
        </div>
    </section>

    <div class="filtros">
        <label class="filtro-buscar">
            <span class="oculto">Buscar cliente</span>
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" class="form-control" placeholder="Buscar cliente" data-filtrar-tabla="#tablaDeudores" autocomplete="off">
        </label>
    </div>

    <div class="panel p-0 tabla-contenedor">
        <table class="tabla-pos" id="tablaDeudores">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="num">Fiado</th>
                    <th class="num">Abonado</th>
                    <th class="num">Debe</th>
                    <th class="acciones-titulo acciones-anchas"><span class="oculto">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @foreach($clientes_deudores as $cliente)
                <tr>
                    <td><a href="{{ route('usuarios.infoDeuda', ['id_cliente' => $cliente->id]) }}"><strong>{{ $cliente->nombre }}</strong></a></td>
                    <td class="num">{{ dinero($cliente->total_fiado) }}</td>
                    <td class="num">{{ dinero($cliente->total_abonado) }}</td>
                    <td class="num"><strong class="texto-deuda">{{ dinero($cliente->total_deuda) }}</strong></td>
                    <td class="acciones">
                        <button type="button" class="btn btn-sm btn-primary js-abonar"
                                data-id="{{ $cliente->id }}" data-nombre="{{ $cliente->nombre }}" data-deuda="{{ $cliente->total_deuda }}">
                            Registrar abono
                        </button>
                        <a class="btn-accion" href="{{ route('usuarios.infoDeuda', ['id_cliente' => $cliente->id]) }}" title="Ver el detalle de la deuda" aria-label="Ver el detalle de la deuda de {{ $cliente->nombre }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="tabla-vacia" data-filtro-vacio="#tablaDeudores" hidden>Ningún cliente con ese nombre debe dinero.</p>
        @if(count($clientes_deudores) === 0)
            <p class="tabla-vacia">Nadie debe dinero en este momento.</p>
        @endif
    </div>
</div>

<div class="modal fade" id="modalAbonar" tabindex="-1" role="dialog" aria-labelledby="tituloAbono" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" id="formAbonar" autocomplete="off">
            @csrf
            <input type="hidden" name="id_cliente" id="abonoCliente">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="tituloAbono">Registrar abono</h2>
                    <p class="modal-subtitulo" id="abonoNombre"></p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-6">
                        <span class="etiqueta-campo">Debe</span>
                        <div class="valor-calculado valor-deuda cifra" id="abonoDeuda"></div>
                    </div>
                    <div class="form-group col-6">
                        <span class="etiqueta-campo">Quedará debiendo</span>
                        <div class="valor-calculado cifra" id="abonoRestante"></div>
                    </div>
                </div>
                <div class="form-group mb-1">
                    <label for="abonoValor">Valor del abono</label>
                    <input class="form-control input-grande cifra" id="abonoValor" inputmode="numeric" required placeholder="$ 0">
                    <input type="hidden" name="total_abonar" id="abonoValorNumero">
                </div>
                <button type="button" class="btn btn-link px-0" id="abonoTodo">Paga todo lo que debe</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="abonoGuardar">Registrar abono</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const $c = id => document.getElementById(id);
    const pesos = n => "$ " + Math.round(Number(n) || 0).toLocaleString("es-CO");
    const digitos = t => Number(String(t || "").replace(/\D/g, "")) || 0;
    let deuda = 0;

    function recalcular() {
        const v = digitos($c("abonoValor").value);
        $c("abonoValor").value = v ? v.toLocaleString("es-CO") : "";
        $c("abonoValorNumero").value = v;
        $c("abonoRestante").textContent = pesos(Math.max(0, deuda - v));
        $c("abonoValor").classList.toggle("is-invalid", v > Math.round(deuda));
    }

    document.querySelectorAll(".js-abonar").forEach(b => b.addEventListener("click", () => {
        deuda = Number(b.dataset.deuda) || 0;
        $c("abonoCliente").value = b.dataset.id;
        $c("abonoNombre").textContent = b.dataset.nombre;
        $c("abonoDeuda").textContent = pesos(deuda);
        $c("abonoValor").value = "";
        recalcular();
        $("#modalAbonar").modal("show");
    }));
    $("#modalAbonar").on("shown.bs.modal", () => $c("abonoValor").focus());
    $c("abonoValor").addEventListener("input", recalcular);
    $c("abonoTodo").addEventListener("click", () => { $c("abonoValor").value = Math.round(deuda); recalcular(); $c("abonoGuardar").focus(); });

    $c("formAbonar").addEventListener("submit", e => {
        e.preventDefault();
        const v = digitos($c("abonoValor").value);
        if (!v) return $c("abonoValor").focus();
        if (v > Math.round(deuda)) {
            Swal.fire({ icon: "warning", title: "El abono es mayor que la deuda", text: "Este cliente debe " + pesos(deuda) + "." });
            return;
        }
        $c("abonoGuardar").disabled = true;
        $.post(@json(route('usuarios.abonar')), $($c("formAbonar")).serialize())
            .then(r => {
                $("#modalAbonar").modal("hide");
                Swal.fire({ icon: "success", title: "Abono registrado", text: pesos(v) + " de " + $c("abonoNombre").textContent + ".", showConfirmButton: false, timer: 1800 });
                setTimeout(() => location.reload(), 1800);
            }, () => {
                Swal.fire({ icon: "error", title: "No se pudo registrar el abono", text: "Revisa la conexión e inténtalo de nuevo." });
                $c("abonoGuardar").disabled = false;
            });
    });
})();
</script>
@endsection
