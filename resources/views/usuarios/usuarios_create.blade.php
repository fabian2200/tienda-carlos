@extends("maestra")
@section("titulo", "Agregar usuario")
@section("contenido")
<div class="pagina pagina-formulario">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('usuarios.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Usuarios</a>
            <h1 class="titulo-pagina">Agregar usuario</h1>
        </div>
    </div>
    <form method="POST" action="{{ route('usuarios.store') }}" autocomplete="off">
        @csrf
        @include("usuarios._formulario", ["usuario" => null])
    </form>
</div>
@endsection
