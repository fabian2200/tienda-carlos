@extends("maestra")
@section("titulo", "Datos del negocio")
@section("contenido")
<style>
    /* Tokens de la nueva dirección visual (pasarán a public/css/pos.css en la fase 3) */
    .negocio-pagina { --hoja:#1E5B45; --hoja-suave:#E3EEE8; --mazorca:#E2A93B; --niebla:#DCE2DE; --tinta-2:#5B6862;
        max-width: 1180px; margin: 24px auto 90px; }
    .negocio-pagina h1 { font-weight: 700; margin-bottom: 4px; }
    .negocio-pagina .sub { color: var(--tinta-2); margin-bottom: 24px; }
    .negocio-pagina .bloque { background: #fff; border: 1px solid var(--niebla); border-radius: 12px; padding: 22px 24px; margin-bottom: 18px; }
    .negocio-pagina .bloque h2 { font-size: 19px; font-weight: 700; margin-bottom: 4px; }
    .negocio-pagina .bloque p.ayuda { color: var(--tinta-2); font-size: 14px; margin-bottom: 16px; }
    .negocio-pagina label { font-weight: 700; font-size: 15px; }
    .negocio-pagina .form-control { height: 46px; font-size: 17px; border-color: var(--niebla); }
    .negocio-pagina .form-control:focus { border-color: var(--hoja); box-shadow: 0 0 0 .2rem var(--hoja-suave); }
    .negocio-pagina .btn-guardar { background: var(--mazorca); border: 0; color: #143F30; font-weight: 700; font-size: 18px; height: 54px; padding: 0 32px; }
    .negocio-pagina .btn-guardar:hover { background: #C98F22; color: #143F30; }
    .logo-actual { width: 96px; height: 96px; border-radius: 14px; border: 1px solid var(--niebla); object-fit: contain; background: #fafbfa; }
    .logo-vacio { width: 96px; height: 96px; border-radius: 14px; border: 2px dashed var(--niebla); display: flex; align-items: center; justify-content: center; color: var(--tinta-2); font-size: 28px; }
    .previa { position: sticky; top: 80px; }
    .previa h2 { font-size: 19px; font-weight: 700; }
    .tirilla { background: #fff; padding: 22px 18px 30px; box-shadow: 0 10px 30px rgba(20,63,48,.14);
        font-family: "Courier New", ui-monospace, monospace; font-size: 13px; line-height: 1.5; color: #2A302D; text-align: center;
        -webkit-mask: conic-gradient(from -45deg at bottom,#0000,#000 1deg 89deg,#0000 90deg) 50%/14px 100%;
                mask: conic-gradient(from -45deg at bottom,#0000,#000 1deg 89deg,#0000 90deg) 50%/14px 100%; }
    .tirilla .titulo, .tirilla .cab { font-weight: 700; }
    .tirilla .sep { border-top: 1.5px dashed #B9C2BD; margin: 10px 0; }
    .tirilla .fila { display: flex; justify-content: space-between; text-align: left; }
</style>

<div class="negocio-pagina">
    <h1>Datos del negocio</h1>
    <p class="sub">Así aparece tu negocio en los tickets, en el PDF de inventario y en el sistema.</p>

    @include("notificacion")
    @if($errors->any())
        <div class="alert alert-danger">
            Revisa estos datos:
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('negocio.update') }}" enctype="multipart/form-data">
        @csrf
        @method("PUT")
        <div class="row">
            <div class="col-lg-7">
                <div class="bloque">
                    <h2>Identificación</h2>
                    <p class="ayuda">Lo primero que se imprime en cada ticket.</p>
                    <div class="form-group">
                        <label for="nombre">Nombre del negocio</label>
                        <input id="nombre" name="nombre" class="form-control js-previa" required maxlength="150" value="{{ old('nombre', $datos->nombre) }}">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nit">NIT</label>
                            <input id="nit" name="nit" class="form-control js-previa" required maxlength="30" value="{{ old('nit', $datos->nit) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="regimen">Régimen <small class="text-muted">(opcional)</small></label>
                            <input id="regimen" name="regimen" class="form-control js-previa" maxlength="80" placeholder="Ej.: No responsable de IVA" value="{{ old('regimen', $datos->regimen) }}">
                        </div>
                    </div>
                </div>

                <div class="bloque">
                    <h2>Ubicación y contacto</h2>
                    <p class="ayuda">Los campos vacíos no se imprimen.</p>
                    <div class="form-group">
                        <label for="direccion">Dirección</label>
                        <input id="direccion" name="direccion" class="form-control js-previa" required maxlength="150" value="{{ old('direccion', $datos->direccion) }}">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="barrio">Barrio</label>
                            <input id="barrio" name="barrio" class="form-control js-previa" maxlength="100" placeholder="Ej.: Alfonso López" value="{{ old('barrio', $datos->barrio) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ciudad">Ciudad</label>
                            <input id="ciudad" name="ciudad" class="form-control js-previa" maxlength="100" value="{{ old('ciudad', $datos->ciudad) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="telefono">Teléfono</label>
                            <input id="telefono" name="telefono" class="form-control js-previa" maxlength="30" value="{{ old('telefono', $datos->telefono) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Correo <small class="text-muted">(no se imprime)</small></label>
                            <input id="email" name="email" type="email" class="form-control" maxlength="120" value="{{ old('email', $datos->email) }}">
                        </div>
                    </div>
                </div>

                <div class="bloque">
                    <h2>Pie del ticket</h2>
                    <p class="ayuda">Lo último que se imprime.</p>
                    <div class="form-group">
                        <label for="mensaje_pie">Mensaje de despedida</label>
                        <input id="mensaje_pie" name="mensaje_pie" class="form-control js-previa" maxlength="255" value="{{ old('mensaje_pie', $datos->mensaje_pie) }}">
                    </div>
                    <div class="form-group">
                        <label for="resolucion_dian">Resolución DIAN <small class="text-muted">(opcional, va en el encabezado)</small></label>
                        <input id="resolucion_dian" name="resolucion_dian" class="form-control js-previa" maxlength="255" value="{{ old('resolucion_dian', $datos->resolucion_dian) }}">
                    </div>
                    <div class="form-group mb-0">
                        <label for="firma_software">Firma del software</label>
                        <input id="firma_software" name="firma_software" class="form-control js-previa" maxlength="120" value="{{ old('firma_software', $datos->firma_software) }}">
                    </div>
                </div>

                <div class="bloque">
                    <h2>Logo</h2>
                    <p class="ayuda">Se muestra en el sistema y en el PDF de inventario. PNG, JPG o WEBP de hasta 2 MB.</p>
                    <div class="d-flex align-items-center" style="gap: 18px">
                        @if($datos->urlLogo())
                            <img class="logo-actual" src="{{ $datos->urlLogo() }}" alt="Logo actual">
                        @else
                            <div class="logo-vacio"><i class="fas fa-store"></i></div>
                        @endif
                        <div>
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control-file">
                            @if($datos->logo)
                                {{-- La casilla va dentro de la etiqueta: maestra oculta los checkbox y estiliza "input + label" --}}
                                <label class="mt-2 mb-0 d-flex align-items-center" style="font-weight: 400; gap: 8px; cursor: pointer">
                                    <input type="checkbox" name="quitar_logo" value="1" style="display: inline-block; width: 18px; height: 18px">
                                    Quitar el logo
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-guardar">Guardar datos del negocio</button>
            </div>

            <div class="col-lg-5">
                <div class="previa">
                    <h2>Así sale el ticket</h2>
                    <p class="text-muted" style="font-size: 14px">Se actualiza mientras escribes. La impresora no imprime tildes, por eso salen sin ellas.</p>
                    <div class="tirilla" aria-live="polite">
                        <div class="titulo">Ticket de venta</div>
                        <div class="cab" id="previa-cabecera"></div>
                        <div>{{ now()->format('Y-m-d H:i:s') }}</div>
                        <div class="sep"></div>
                        <div class="fila"><span>2.00 Und x Arroz Diana 500 g</span><span>$4600</span></div>
                        <div class="fila"><span>1.00 Und x Huevos AA x 30</span><span>$16500</span></div>
                        <div class="sep"></div>
                        <div class="fila" style="font-weight:700;font-size:16px"><span>Total:</span><span>$21100</span></div>
                        <div class="sep"></div>
                        <div id="previa-pie"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Misma lógica que App\Services\TicketService::lineasEncabezado()
    (function () {
        const sinTildes = t => t.replace(/[áéíóúüÁÉÍÓÚÜñÑ]/g, c => ({"á":"a","é":"e","í":"i","ó":"o","ú":"u","ü":"u","Á":"A","É":"E","Í":"I","Ó":"O","Ú":"U","Ü":"U","ñ":"n","Ñ":"N"})[c]);
        const v = id => document.getElementById(id).value.trim();
        const escapar = t => t.replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[c]);

        function pintar() {
            const lineas = [v("nombre")];
            if (v("nit")) lineas.push("NIT " + v("nit"));
            if (v("regimen")) lineas.push(v("regimen"));
            if (v("direccion")) lineas.push(v("direccion"));
            if (v("barrio")) lineas.push(/^(brr|barrio)\b/i.test(v("barrio")) ? v("barrio") : "Brr. " + v("barrio"));
            if (v("ciudad")) lineas.push(v("ciudad"));
            if (v("telefono")) lineas.push("Tel. " + v("telefono"));
            if (v("resolucion_dian")) lineas.push(v("resolucion_dian"));
            document.getElementById("previa-cabecera").innerHTML = lineas.map(l => escapar(sinTildes(l))).join("<br>");

            const pie = [];
            if (v("mensaje_pie")) pie.push(v("mensaje_pie"));
            if (v("firma_software")) pie.push("<br>" + escapar(sinTildes(v("firma_software"))));
            document.getElementById("previa-pie").innerHTML = pie.map((l, i) => i === 0 ? escapar(sinTildes(l)) : l).join("<br>");
        }

        document.querySelectorAll(".js-previa").forEach(el => el.addEventListener("input", pintar));
        pintar();
    })();
</script>
@endsection
