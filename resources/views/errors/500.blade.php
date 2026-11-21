@extends('errors.plantilla')
@section('titulo', 'Algo salió mal')
@section('icono', 'fa-tools')
@section('mensaje', 'El sistema tuvo un error al hacer esta operación. Si estabas cobrando, revisa la lista de Ventas antes de repetirla para no duplicarla.')
@section('acciones')
    <a class="btn btn-secondary" href="{{ url('/ventas') }}">Ver ventas</a>
@endsection
