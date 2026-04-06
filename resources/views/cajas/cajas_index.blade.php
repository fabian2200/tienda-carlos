@extends("maestra")
@section("titulo", "Cajas")
@section("contenido")
<br>
<div class="row">
    <div class="col-12">
        <div class="row">
            <div style="padding: 20px;" class="col-lg-3">
                <div class="card_ventas" style="background-color: rgb(6, 139, 247);">
                    <div style="width: 100%">
                        <h3><strong>Total Vendido (Hoy)</strong></h3>
                    </div> 
                    <h1>$ {{ number_format($totalVendidoHoy, 2) }}</h1>
                    <i style="opacity: .5; font-size: 50px; position: absolute; right: 30px; bottom: 30px" class="fas fa-donate"></i>
                </div>
            </div>
            <div class="col-lg-3">

            </div>
            <div style="padding: 20px;" class="col-lg-3">
                <div class="card_ventas" style="background-color: rgb(4, 95, 1);">
                    <div style="width: 100%">
                        <h3><strong>Total Vendido (Mes)</strong></h3>
                    </div> 
                    <h1>$ {{ number_format($totalVendidoMensual, 2) }}</h1>
                    <i style="opacity: .5; font-size: 50px; position: absolute; right: 30px; bottom: 30px" class="fas fa-cash-register"></i>
                </div>
            </div>
            <div style="padding: 20px;" class="col-lg-3">
                <div class="card_ventas" style="background-color: rgb(247, 94, 6);">
                    <div style="width: 100%">
                        <h3><strong>Total fiado</strong></h3>
                    </div> 
                    <h1>$ {{ number_format($totalFiado, 2) }}</h1>
                    <i style="opacity: .5; font-size: 50px; position: absolute; right: 30px; bottom: 30px" class="fas fa-hand-holding-usd"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 mt-3">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Cajas de Venta</h5>
            </div>
        </div>
    </div>
    <div class="col-12 mt-3">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr style="background-color:rgb(219, 237, 255); color: #000;">
                            <th>Caja</th>
                            <th>Total Ventas Mensual</th>
                            <th>Total Ventas Hoy</th>
                            <th>Total Fiado</th>   
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cajas as $caja)
                            <tr>
                                <td>{{ $caja->name }}</td>
                                <td>{{ number_format($caja->total_ventas_mensual, 2, ",", ".") }}</td>
                                <td>{{ number_format($caja->total_ventas_hoy, 2, ",", ".") }}</td>
                                <td>{{ number_format($caja->total_fiado, 2, ",", ".") }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection