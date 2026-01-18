@extends("maestra")
@section("titulo", "Domicilios")
@section("contenido")
<br>
<h1 style="width: 100%; text-align: center"><strong>Listado de domicilios</strong></h1>
<hr>
<div class="row">
    <div class="col-12">
        @include("notificacion")
        <div class="table-responsive">
            <table id="tabla_ventas" class="table table-bordered">
                <thead style="background-color: #91baee">
                    <tr>
                        <th>Cliente</th>
                        <th>Direccion</th>
                        <th>Subtotal</th>
                        <th>Domicilio</th>
                        <th>Total</th>
                        <th>Pago</th>
                        <th>Vueltos</th>
                        <th>Fecha</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody id="tabla_domicilios">
               
                </tbody>
            </table>
            <br><br><br>
        </div>
    </div>
</div>

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-body">
            <div class="row">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label style="font-size: 20px" for="">Imprimir factura</label>
                        <select style="font-size: 15px !important" name="imprimir_factura" id="imprimir_factura" class="form-control">
                            <option value="no">no</option>
                            <option value="si">si</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label style="font-size: 20px" for="">Cliente</label>
                        <input style="font-size: 15px !important" autocomplete="off" id="nombre_cliente" name="nombre_cliente" style="font-size: 20px" class="form-control" readonly type="text">
                        <input autocomplete="off" id="direccion_cliente" name="direccion_cliente" style="font-size: 20px" class="form-control" readonly type="hidden">
                        <input autocomplete="off" id="celular_cliente" name="celular_cliente" style="font-size: 20px" class="form-control" readonly type="hidden">
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label style="font-size: 20px" for="">Dirección del cliente</label>
                        <input readonly style="font-size: 15px !important" id="direccion_cliente_ver" autocomplete="off" required name="direccion_cliente_ver" style="font-size: 20px" class="form-control" type="text">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label style="font-size: 20px" for="">Total a pagar</label>
                        <input readonly style="font-size: 15px !important" id="total_pagar_tv" autocomplete="off" required name="total_pagar" style="font-size: 20px" class="form-control" type="text">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 20px" for="">Total Cambio</label>
                        <input readonly style="font-size: 15px !important" autocomplete="off" required name="total_vueltos" id="vueltos" style="font-size: 20px" class="form-control" type="text">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label style="font-size: 20px" for="">Total Dinero</label>
                        <input readonly style="font-size: 15px !important" autocomplete="off" id="total_dinero" required name="total_dinero" oninput="calcularCambio(this)" style="font-size: 20px" class="form-control" type="text">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 20px" for="">Total Fiado</label>
                        <input readonly style="font-size: 15px !important" autocomplete="off" id="fiado" required name="total_fiado" style="font-size: 20px" class="form-control" type="currency">
                    </div>
                </div>
            </div>
            <hr>
            <table style="font-size: 15px !important" class="table table-bordered">
                <thead>
                    <tr style="background-color: #75caeb;">
                        <th>Código de barras</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="tbodyTablaProductos">
                   
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button onclick="guardarVenta()" type="button" class="btn btn-primary">Guardar Venta</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    function obtenerDomicilios(){
        $.ajax({
            url: 'https://provisiones-carlosandres.shop/ver_domicilios.php',
            type: 'GET',
            success: function(response) {
                response = JSON.parse(response);
                var div = "";
                response.forEach(element => {
                    div += "<tr>";
                    div += "<td>"+element.nombre+"</td>";
                    div += "<th>"+element.direccion+"</th>";
                    div += "<td>"+formatMonedaCOP(element.total_pagar)+"</td>";
                    div += "<td>"+formatMonedaCOP(element.precio_domi)+"</td>";
                    div += "<td>"+formatMonedaCOP(parseFloat(element.total_pagar) + parseFloat(element.precio_domi))+"</td>";
                    div += "<td>"+formatMonedaCOP(parseFloat(element.total_pagar) + parseFloat(element.precio_domi) + parseFloat(element.vueltos_domi))+"</td>";
                    div += "<td>"+formatMonedaCOP(element.vueltos_domi)+"</td>";
                    div += "<td>"+element.fecha_domi+"</td>";
                    div += "<td><button onclick='obtenerInfoPedido("+element.id+")' data-toggle='modal' data-target='#exampleModal' class='btn btn-success'>Despachar</button></td>";
                    div += "</tr>";
                });

                document.getElementById("tabla_domicilios").innerHTML = div;
            }
        });
        return false;
    }

    function formatMonedaCOP(valor) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0
        }).format(valor);
    }

    obtenerDomicilios();

    setInterval(obtenerDomicilios, 10000);

    var total_pagar = 0;
    var productos = [];
    var id_pedido_sel = "";
    var precio_domi = 0;

    function obtenerInfoPedido(id_pedido){
        id_pedido_sel = id_pedido;
        $.ajax({
            url: 'https://provisiones-carlosandres.shop/info_pedido.php?id_pedido='+id_pedido,
            type: 'GET',
            success: function(response) {
                response = JSON.parse(response);
                response.precio_domi = parseFloat(response.precio_domi);
                response.vueltos_domi = parseFloat(response.vueltos_domi);
                response.total_pagar = parseFloat(response.total_pagar);
                precio_domi = parseFloat(response.precio_domi);
                var div = "";
                total_pagar = response.total_pagar;
                response.productos.forEach(element => {
                    element.cantidad = parseFloat(element.cantidad)
                    element.precio = parseFloat(element.precio)
                    var cantidad_texto = "";
                    if(element.unidad == "Kilos"){
                        cantidad_texto = decimalAPesoTexto(element.cantidad)+" => "+element.cantidad +" Kg"
                    }else{
                        cantidad_texto = element.cantidad
                    }

                    div += "<tr>"+
                        "<td>"+element.codigo_barras+"</td>"+
                        "<td>"+element.descripcion+"</td>"+
                        "<td>"+element.precio+"</td>"+
                        "<td>"+cantidad_texto+"</td>"+
                        "<td>$ "+(redondearA100(element.cantidad * element.precio))+"</td>"+
                    "</tr>";
                });
                

                productos = response.productos;
                div += "<tr>"+
                    "<th colspan='4'>Subtotal</th>"+
                    "<th>"+formatMonedaCOP(total_pagar)+"</th>"+
                "</tr>";

                div += "<tr>"+
                    "<th colspan='4'>Domicilio</th>"+
                    "<th>"+formatMonedaCOP(response.precio_domi)+"</th>"+
                "</tr>";

                div += "<tr style='background-color: #75caeb;'>"+
                    "<th colspan='4'>Total</th>"+
                    "<th>"+formatMonedaCOP(total_pagar + response.precio_domi)+"</th>"+
                "</tr>";

                total_pagar = total_pagar + response.precio_domi;

                document.getElementById("tbodyTablaProductos").innerHTML = div;
                document.getElementById("total_pagar_tv").value = total_pagar;
                document.getElementById("nombre_cliente").value = response.nombre;
                document.getElementById("direccion_cliente").value = response.direccion;  
                document.getElementById("direccion_cliente_ver").value = response.direccion;
                document.getElementById("celular_cliente").value = response.celular; 
                document.getElementById("vueltos").value = response.vueltos_domi;
                document.getElementById("total_dinero").value = (total_pagar + response.vueltos_domi);
                
                var tf = total_pagar - (total_pagar + response.vueltos_domi);

                if(tf > 0){
                    document.getElementById("fiado").value = tf;
                }else{
                    document.getElementById("fiado").value = 0;
                }
                
            }
        });
        return false;
    }

    function redondearA100(valor){
        return Math.round(valor / 100) * 100;
    }

    function decimalAPesoTexto(valor) {
        if (isNaN(valor) || valor < 0) return 'Valor inválido';

        // Convertimos a gramos
        const gramosTotales = Math.round(valor * 1000);

        // Si es 1 kilo o más → kilos y gramos
        if (gramosTotales >= 1000) {
            const kilos = Math.floor(gramosTotales / 1000);
            const gramos = gramosTotales % 1000;

            if (gramos === 0) {
                return kilos === 1 ? '1 kilo' : `${kilos} kilos`;
            }

            return `${kilos} kilo${kilos > 1 ? 's' : ''} y ${gramos} gramos`;
        }

        // Menos de 1 kilo → libras
        // 1 libra = 500 gramos
        if (gramosTotales === 250) return 'Media libra';
        if (gramosTotales === 500) return '1 Libra';
        if (gramosTotales === 750) return '1 Libra y media';

        // Cualquier otro caso menor a 1 kilo
        return `${gramosTotales} gramos`;
    }


    function calcularCambio(element){
        var valor = (-1) * (total_pagar - element.value).toFixed(3)
        document.getElementById("vueltos").value = valor;
        if(valor < 0){
            document.getElementById("fiado").value = (-1) * valor;
        }else{
            document.getElementById("fiado").value = 0;
        }
    }

    function guardarVenta(){
        if(document.getElementById("total_dinero").value != ""){
            var datos = {
                total_pagar: total_pagar - precio_domi,
                precio_domi: precio_domi,
                total_dinero: document.getElementById("total_dinero").value,
                total_fiado: document.getElementById("fiado").value,
                total_vueltos: document.getElementById("vueltos").value,
                imprimir_factura: document.getElementById("imprimir_factura").value,
                celular_cliente: document.getElementById("celular_cliente").value,
                nombre_cliente: document.getElementById("nombre_cliente").value,
                direccion_cliente: document.getElementById("direccion_cliente").value,
                productos: productos,
                id_pedido:  id_pedido_sel
            }

            $.ajax({
                url: "/terminarVentaDomicilio",
                type: "POST",
                contentType: "application/json",
                data: JSON.stringify(datos),
                beforeSend: function() {
                    // Mostrar el mensaje de carga antes de enviar la solicitud
                    Swal.fire({
                        title: 'Guardando venta...',
                        text: 'Por favor espere mientras procesamos su solicitud',
                        icon: 'info',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        willOpen: () => {
                            Swal.showLoading(); // Muestra el icono de carga
                        }
                    });
                },
                success: function(respuesta) {
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: "Venta realizada correctamente",
                        showConfirmButton: false,
                        timer: 2000
                    });
                
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                },
            });

        }else{
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Ingrese la cantidad de dinero que el cliente pago en en campo (total dinero)",
                showConfirmButton: false,
                timer: 4000
            });
        }
    }
  </script>
@endsection