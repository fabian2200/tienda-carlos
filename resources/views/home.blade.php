@extends('maestra')
@section("titulo", "Inicio")
@section('contenido')
@php
    $modulos = ["cajas", "productos", "ventas", "clientes", "usuarios"];
@endphp
    <div class="col-12 pb-2">
        <div class="row">
            @php
                $colores = ['info', 'primary', 'warning', 'morado', 'gris'];
            @endphp

            @foreach($modulos as $index => $modulo)
                @if($modulo != "cajas")
                    <div class="col-12 col-md-4" style="margin-top: 20px">
                        <div class="card" style="align-items: center; border: none; margin: 20px;">
                            <a style="width: 210px; display: flex; flex-direction: column; padding: 20px; align-items: center; justify-content: center; border-radius: 20px; border-width: 0 0px 10px 0px;" href="{{route("$modulo.index")}}" class="btn btn-{{ $colores[$index % count($colores)] }}">
                                <img style="height: 120px; width: fit-content; padding: 15px" class="card-img-top" src="{{url("/img/$modulo.png")}}">
                                <h5 style="font-weight: bolder;">{{ucwords($modulo)}}</h5>
                            </a>
                        </div>
                    </div>
                @endif
                @if($modulo == "cajas" && Auth::user()->email == "admin")
                    <div class="col-12 col-md-4" style="margin-top: 20px">
                        <div class="card" style="align-items: center; border: none; margin: 20px;">
                            <a style="width: 210px; display: flex; flex-direction: column; padding: 20px; align-items: center; justify-content: center; border-radius: 20px; border-width: 0 0px 10px 0px;" href="{{route("cajas.index")}}" class="btn btn-success">
                                <img style="height: 120px; width: fit-content; padding: 15px" class="card-img-top" src="/img/cajas.png">
                                <h5 style="font-weight: bolder;">Cajas</h5>
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach
            <div class="col-12 col-md-4" style="margin-top: 20px">
                <div class="card" style="align-items: center; border: none; margin: 20px;">
                    <a style="width: 210px; display: flex; flex-direction: column; padding: 20px; align-items: center; justify-content: center; border-radius: 20px; border-width: 0 0px 10px 0px;" href="{{route("usuarios.deudores")}}" class="btn btn-danger">
                        <img style="height: 120px; width: fit-content; padding: 15px" class="card-img-top" src="/img/prestamo.png">
                        <h5 style="font-weight: bolder;">Clientes Deudores</h5> 
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
