@extends("maestra")
@section("titulo", "Agregar proveedor")
@section("contenido")
<div class="pagina pagina-formulario">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('proveedores.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Proveedores</a>
            <h1 class="titulo-pagina">Agregar proveedor</h1>
        </div>
    </div>
    <form method="POST" action="{{ route('proveedores.store') }}" autocomplete="off">
        @csrf
        @include("proveedores._formulario", ["proveedor" => null])
    </form>
</div>
@endsection
