@extends('errors.plantilla')
@section('titulo', 'La sesión venció')
@section('icono', 'fa-clock')
@section('mensaje', 'La página estuvo abierta mucho tiempo y no se guardó lo que enviaste. Vuelve a la página, recárgala e inténtalo de nuevo.')
@section('acciones')
    <a class="btn btn-secondary" href="javascript:history.back()">Volver</a>
@endsection
