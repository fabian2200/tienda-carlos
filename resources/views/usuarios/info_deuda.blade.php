@extends("maestra")
@section("titulo", "Usuarios")
@section("contenido")
<br>
<div class="row">
    <div class="col-12">
        <div class="row" style="padding-left: 5%; padding-right: 5%">
            <div class="col-lg-5" style="display: flex; align-items: center">
                <h3><i class="fas fa-user"></i> Cliente: <span style="color: green">{{ $cliente->nombre }}</span>  </h3>
            </div>
            <div class="col-lg-4" style="display: flex; align-items: center">
                <h3 style="color: red; font-weight: bold">Deuda total:  ${{number_format($cliente->total_deuda, 2)}}</h3>
            </div>
            <div class="col-lg-3">
                <button onclick="ImprimirDeuda({{$cliente->id}})" style="font-size: 20px; width: 100%" class="btn btn-success">Imprimir deuda</button>
            </div>
        </div>
               <hr>
        @include("notificacion")
        <div class="table-responsive">
           @foreach ($facturas_deudas as $item)
            <div style="padding-left: 15%; padding-right: 15%">
                <div class="row">
                    <div class="col-lg-12">
                        <h2 style="color: rgb(42, 54, 165); font-weight: bold; margin-bottom: 0px">Factura # {{$item->id}}</h2>
                        <h6 style="color: rgb(4, 6, 22); font-weight: bold">Fecha {{$item->fecha_venta}}</h6>
                        <h4 style="color: rgb(165, 42, 42); font-weight: bold">Total Fiado en esta factura $ {{$item->total_fiado}}</h4>
                    </div>
                </div>
                <br>
                <table style="width: 100%">
                    <tr style="background-color: aquamarine">
                        <th style="width: 40%">Producto</th>
                        <th style="width: 30%">Cantidad</th>
                        <th style="width: 30%">Precio</th>
                    </tr>
                    @foreach ($item->productos as $item2)
                        <tr>
                            <td>{{$item2->descripcion}}</td>
                            <td>{{$item2->cantidad}} {{$item2->unidad}}</td>
                            <td>$ {{$item2->precio}}</td>
                        </tr>
                    @endforeach
                    <tr style="background-color: aquamarine">
                        <th colspan="2">Total Factura</th>
                        <th>$ {{ $item->total_pagar }}</th>
                    </tr>
                </table>
                <br>
                <hr>
                <br>
            </div>
           @endforeach
           <div class="row" style="width: 100%; padding-left: 15%; padding-right: 15%">
                <div class="col-lg-6">
                    <h2 style="color: rgb(250, 0, 0); font-weight: bold">&nbsp;&nbsp;Total Fiado ${{$cliente->total_fiado}}</h2>
                    <h2 style="color: rgb(11, 99, 33); font-weight: bold">- Total Abonado ${{$cliente->total_abonado}}</h2>
                    <hr>
                    <h2 style="color: rgb(2, 2, 2); font-weight: bold">&nbsp;&nbsp;&nbsp;Deuda Total ${{$cliente->total_deuda}}</h2>
                </div>
            </div>
        </div>
    </div>
</div>
<br>
<br>
<br>
<br>
<script>
    function ImprimirDeuda(id_cliente){
        $.ajax({
            url: '/imprimir-deuda?id_cliente='+id_cliente,
            type: 'GET',
            success: function(response) {
                alert(response.mensaje);
            }
        });
    }
</script>
@endsection
