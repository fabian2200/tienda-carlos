@extends('errors.plantilla')
@section('titulo', 'Esta página no existe')
@section('icono', 'fa-search')
@section('mensaje', 'Puede que el enlace esté mal escrito o que lo que buscabas se haya eliminado.')
@section('acciones')
    <a class="btn btn-secondary" href="javascript:history.back()">Volver</a>
@endsection
