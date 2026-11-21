@extends('errors.plantilla')
@section('titulo', 'No tienes acceso a esta página')
@section('icono', 'fa-lock')
@section('mensaje', $exception->getMessage() ?: 'Esta sección es solo para el administrador. Si la necesitas, pídele que entre con su usuario.')
