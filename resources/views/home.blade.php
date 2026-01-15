@extends('maestra')
@section("titulo", "Inicio")
@section('contenido')
    @foreach([
    ["vender", "productos", "ventas", "clientes", "usuarios"],
    ] as $modulos)
        <div class="col-12 pb-2">
            <div class="row">
                @php
                    $colores = ['success', 'primary', 'warning', 'morado', 'gris'];
                @endphp

                @foreach($modulos as $index => $modulo)
                    <div class="col-12 col-md-4" style="margin-top: 20px">
                        <div class="card" style="align-items: center; border: none; margin: 20px;">
                            <a style="width: 210px; display: flex; flex-direction: column; padding: 20px; align-items: center; justify-content: center; border-radius: 20px; border-width: 0 0px 10px 0px;" href="{{route("$modulo.index")}}" class="btn btn-{{ $colores[$index % count($colores)] }}">
                                <img style="height: 120px; width: fit-content; padding: 15px" class="card-img-top" src="{{url("/img/$modulo.png")}}">
                                <h5 style="font-weight: bolder;">{{$modulo === "acerca_de" ? "Acerca de" : ucwords($modulo)}}</h5>
                            </a>
                        </div>
                    </div>
                @endforeach
                <div class="col-12 col-md-4" style="margin-top: 20px">
                    <div class="card" style="align-items: center; border: none; margin: 20px;">
                        <a style="width: 210px; display: flex; flex-direction: column; padding: 20px; align-items: center; justify-content: center; border-radius: 20px; border-width: 0 0px 10px 0px;" href="{{route("$modulo.deudores")}}" class="btn btn-danger">
                            <img style="height: 120px; width: fit-content; padding: 15px" class="card-img-top" src="/img/prestamo.png">
                            <h5 style="font-weight: bolder;">Clientes Deudores</h5> 
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
